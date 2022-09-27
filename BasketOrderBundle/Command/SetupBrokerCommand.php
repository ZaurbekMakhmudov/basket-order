<?php

namespace App\BasketOrderBundle\Command;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\Impl\AmqpBind;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CacheClearAccountCommand
 * @package App\BasketOrderBundle\Command
 */
class SetupBrokerCommand extends Command
{
    protected static $defaultName = 'setup:broker';

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $connection = new AmqpConnectionFactory($_ENV['ENQUEUE_DSN']);
        $context = $connection->createContext();
        $topic = $context->createTopic('gateway.main');
        $topic->setArgument('durable', true);
        $topic->setFlags(AmqpDestination::FLAG_DURABLE);
        $topic->setType('topic');
        $amqpQueue = $context->createQueue('global.shop.coupon.import');
        $amqpQueue->addFlag(AmqpQueue::FLAG_DURABLE);
        $context->declareQueue($amqpQueue);
        $context->declareTopic($topic);
        $context->bind(new AmqpBind($topic, $amqpQueue, 'global.cmb.product.notify'));

    }
}