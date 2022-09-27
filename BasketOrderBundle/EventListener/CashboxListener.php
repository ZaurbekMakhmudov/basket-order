<?php

namespace App\BasketOrderBundle\EventListener;

use App\BasketOrderBundle\Helper\ShopConst;
use App\BasketOrderBundle\Service\OrderService;
use App\CashboxBundle\Event\CashboxSaveEvent;
use Exception;
use Psr\Log\LoggerInterface;
use WebPlatform\InGatewayBundle\Communicator\Communicator;

/**
 * Class CashboxListener
 * @package App\BasketOrderBundle\EventListener
 */
class CashboxListener
{
    private LoggerInterface $logger;

    private OrderService $orderService;

    private Communicator $communicator;

    public function __construct(LoggerInterface $logger, OrderService $orderService, Communicator $communicator)
    {
        $this->logger = $logger;
        $this->orderService = $orderService;
        $this->communicator = $communicator;
    }

    /**
     * @param CashboxSaveEvent $event
     * @throws Exception
     */
    public function processReceiptOnline(CashboxSaveEvent $event)
    {
        $out = [];
        $logStr = 'processReceiptOnline: ';
        try {
            $receiptOnline = $event->getReceiptOnline();
            $basketId = $receiptOnline->getBasketId();
            $receiptOnlineId = $receiptOnline->getReceiptOnlineId();
            $identifier = $receiptOnline->getIdentifier();
            $out = $this->orderService->updateOrderStatusFromReceiptOnline($basketId, $this->communicator, $receiptOnlineId, $identifier);
        } catch (Exception $e) {
            $this->logger->error($logStr . $e->getMessage(), $out);
        }
    }

}