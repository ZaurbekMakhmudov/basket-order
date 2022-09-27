<?php

namespace App\BasketOrderBundle\Processor;


use App\BasketOrderBundle\Service\QueueService;
use Interop\Queue\Message;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\QueueSubscriberInterface;

/**
 * Class CouponProcessor
 * @package App\BasketOrderBundle\Processor
 */
class CouponProcessor implements Processor, CommandSubscriberInterface, QueueSubscriberInterface
{
    const QUEUE_NAME = 'global.shop.coupon.import';

    private QueueService $queueService;

    public function __construct(QueueService $queueService) {
        $this->queueService = $queueService;
    }

    /**
     * @inheritDoc
     */
    public function process(Message $message, Context $context)
    {
        $this->reconnect();
        $this->queueService->processing($message->getBody());

        return self::ACK;
    }

    /**
     * @return array
     */
    public static function getSubscribedCommand(): array
    {
        $subscriberCommand = [];
        foreach (self::getQueueNames() as $queueName) {
            $subscriberCommand[] = [
                'command' => $queueName,
                'queue' => $queueName,
                'prefix_queue' => false,
                'exclusive' => true,
                'name' => $queueName,
            ];
        }

        return $subscriberCommand;
    }

    /**
     * @return array
     */
    public static function getSubscribedQueues(): array
    {
        return self::getQueueNames();
    }

    /**
     * @return array
     */
    private static function getQueueNames(): array
    {
        return [self::QUEUE_NAME]; // todo: read from env
    }

    private function reconnect()
    {
        $connection = $this->queueService->entityManager->getConnection();
        if ($connection->ping() === false) {
            $connection->close();
            $connection->connect();
        }
    }

}
