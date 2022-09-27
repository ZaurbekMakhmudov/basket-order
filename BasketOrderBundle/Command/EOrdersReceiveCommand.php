<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 12.08.19
 * Time: 20:23
 */

namespace App\BasketOrderBundle\Command;

use App\BasketOrderBundle\Helper\DateTimeHelper;
use App\BasketOrderBundle\Service\OrderService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 *
 *
 * php bin/console order:e_orders_receive --dataInput=response_1577451149578.json
 *
 *
 * Class App\BasketOrderBundle\Command\OrdersReceiveCommand
 * @package App\BasketOrderBundle\Command
 */
class EOrdersReceiveCommand extends Command
{
    protected static $defaultName = 'order:e_orders_receive';
    /** @var OrderService */
    protected $orderService;
    /** @var  OutputInterface */
    private $output;
    /** @var ContainerInterface  */
    protected $container;
    protected $webDir;
    protected $kernelProjectFolder;

    public function __construct(OrderService $orderService, $kernelProjectFolder)
    {
        $this->orderService = $orderService;
        $this->webDir = $kernelProjectFolder . '/var/storage';

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('загрузить данные из файла базу eshop')
            ->addOption(
                'dataInput',
                'di',
                InputOption::VALUE_REQUIRED,
                'название файла',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $output->writeln('Начало запроса ' . DateTimeHelper::getInstance()->getDateString());
        $ymTime = microtime(true);
        $dataInput = $input->getOption('dataInput');
        $content = $this->getFileContent($dataInput);

        $out = $this->orderService->receiveEshopDump($output, $content);

        $ymTime = (string)(microtime(true) - $ymTime);
        $output->writeln("Затрачено время " . $ymTime);
        $output->writeln('Завершение запроса ' . DateTimeHelper::getInstance()->getDateString());
    }
    protected function getFileContent($path)
    {
        $content = null;
        if($path !== null){
            $fs = new Filesystem();
            $path = $this->webDir . '/' . $path;
            if($fs->exists($path)){
                $content = file_get_contents($path);
            }
        }

        return $content;
    }
}