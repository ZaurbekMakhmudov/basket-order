<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 12.08.19
 * Time: 20:23
 */

namespace App\BasketOrderBundle\Command;

use App\BasketOrderBundle\Helper\AppHelper;
use App\BasketOrderBundle\Helper\DateTimeHelper;
use App\BasketOrderBundle\Service\OrderService;
use Metaer\CurlWrapperBundle\CurlWrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WebPlatform\InGatewayBundle\Communicator\Communicator;

/**
 *
 * раз в 6 мин, $eshopOrderStatusAviable
 *  php bin/console order:orders_receive
 *
 * для выборки только раз в сутки, только статусы $eshopOrderStatusDay
 *  php bin/console order:orders_receive --days=1
 *
 * для выборки только раз в сутки, только STATUS_ONL
 *  php bin/console order:orders_receive --days=ONL
 *
 * для выборки только раз в сутки, только статусы STATUS_ISS
 *  php bin/console order:orders_receive --days=ISS
 *
 * для выборки только раз в сутки, только статусы STATUS_RFC
 *  php bin/console order:orders_receive --days=RFC
 *
 * dev
 * *\/6 * * * * php /home/shop/current/bin/console order:orders_receive          >/dev/null 2>&1
 * @daily      php /home/shop/current/bin/console order:orders_receive --days=ONL >/dev/null 2>&1
 * @daily      php /home/shop/current/bin/console order:orders_receive --days=ISS >/dev/null 2>&1
 * @daily      php /home/shop/current/bin/console order:orders_receive --days=RFC >/dev/null 2>&1
 *
 *
 * prod
 * *\/6 * * * * /usr/bin/flock -n /tmp/shop_orders_receive.lock -c "php /home/shop/current/bin/console order:orders_receive --env=prod >> /home/shop/shared/var/log/orders_receive.log 2>&1"
 * @daily /usr/bin/flock -n /tmp/shop_orders_receive_day.lock -c "php /home/shop/current/bin/console order:orders_receive  --days=ONL --env=prod >> /home/shop/shared/var/log/orders_receive_day.log 2>&1"
 * @daily /usr/bin/flock -n /tmp/shop_orders_receive_day.lock -c "php /home/shop/current/bin/console order:orders_receive  --days=ISS --env=prod >> /home/shop/shared/var/log/orders_receive_day.log 2>&1"
 * @daily /usr/bin/flock -n /tmp/shop_orders_receive_day.lock -c "php /home/shop/current/bin/console order:orders_receive  --days=RFC --env=prod >> /home/shop/shared/var/log/orders_receive_day.log 2>&1"
 * @daily /usr/bin/flock -n /tmp/shop_orders_receive_day.lock -c "php /home/shop/current/bin/console order:orders_receive  --days=1 --env=prod >> /home/shop/shared/var/log/orders_receive_day.log 2>&1"
 *
 * для выборки по номеру заказа
 *  php bin/console order:orders_receive --code="UR-19354-80"
 *
 * Class App\BasketOrderBundle\Command\OrdersReceiveCommand
 * @package App\BasketOrderBundle\Command
 */
class OrdersReceiveCommand extends Command
{
    protected static $defaultName = 'order:orders_receive';
    /** @var OrderService */
    protected $orderService;
    /** @var  CurlWrapper */
    protected $curlWrapper;
    /** @var  Communicator */
    private $communicator;
    /** @var  OutputInterface */
    private $output;

    public function __construct(OrderService $orderService, CurlWrapper $curlWrapper)
    {
        $this->orderService = $orderService;
        $this->curlWrapper = $curlWrapper;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('проверить статус заказов в таблице')
            //->setHelp('This command allows you to create a user...')
            //->addArgument('password', $this->requirePassword ? InputArgument::REQUIRED : InputArgument::OPTIONAL, 'User password')
            ->addOption(
                'days',
                'ds',
                InputOption::VALUE_REQUIRED,
                'один раз в сутки',
                false
            )
            ->addOption(
                'code',
                'cd',
                InputOption::VALUE_REQUIRED,
                'номер заказа',
                false
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '4096M');
        set_time_limit(0);
        $this->output = $output;
        $output->writeln('Начало запроса ' . DateTimeHelper::getInstance()->getDateString());
        $ymTime = microtime(true);
        $days = $input->getOption('days');
        $code = $input->getOption('code');

        $out = $this->orderService->receiveEshopStatus($output, $this->getInGatewayCommunicator(), $days, $code);
        $output->writeln('message ' . (isset($out['message']) ?
                AppHelper::jsonFromArray($out['message']) : 'not out') . ', status http ' .
            (isset($out['httpAnswer']) ? AppHelper::jsonFromArray($out['httpAnswer']) : 'not out'));
        $ymTime = (string)(microtime(true) - $ymTime);
        $output->writeln("Затрачено время " . $ymTime);
        $output->writeln('Завершение запроса ' . DateTimeHelper::getInstance()->getDateString());
    }

    /**
     * @param Communicator $communicator
     */
    public function setInGatewayCommunicator(Communicator $communicator)
    {
        $this->communicator = $communicator;
    }

    /**
     * @return Communicator
     */
    public function getInGatewayCommunicator()
    {
        return $this->communicator;
    }

}