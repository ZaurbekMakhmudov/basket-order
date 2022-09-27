<?php
namespace App\BasketOrderBundle\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Enqueue\Symfony\Client\ConsumeCommand;

class CouponImportCommand extends ConsumeCommand
{

    protected static $defaultName = 'coupon:import';

    public function __construct(
        ContainerInterface $container,
        string $defaultClient,
        string $queueConsumerIdPattern = 'enqueue.client.%s.queue_consumer',
        string $driverIdPattern = 'enqueue.client.%s.driver',
        string $processorIdPatter = 'enqueue.client.%s.delegate_processor'
    ) {
        parent::__construct($container, $defaultClient, $queueConsumerIdPattern, $driverIdPattern, $processorIdPatter);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $command = $this->getApplication()->find('setup:broker');
        $arguments = [];
        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);
        sleep(3);
        $command = $this->getApplication()->find('enqueue:consume');
        $arguments = ['-c' => 'couponimport'];
        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);

        return 0;
    }
}