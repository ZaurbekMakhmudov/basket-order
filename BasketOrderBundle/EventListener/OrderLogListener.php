<?php

declare(strict_types=1);

namespace App\BasketOrderBundle\EventListener;

use App\BasketOrderBundle\Entity\OrderRequest;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Слушатель для логирования запроса, по ID заказа.
 */
class OrderLogListener
{
    /** @var ObjectManager */
    protected ObjectManager $em;

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($event->getResponse()->getStatusCode() === Response::HTTP_OK) {
            $request = $event->getRequest();
            $patchInfo = $request->getPathInfo();
            $content = $event->getResponse()->getContent();
            $orderId = $request->get('number');

            if ('/order' === $patchInfo) {
                $orderId = json_decode($content, true)['order']['order_id'] ?? null;
            }

            if (preg_match('/\/basket(.*)/', $patchInfo)) {
                $orderId = json_decode($content, true)['basket']['order_id'] ?? null;
            }

            if (!empty($orderId)) {
                $orderRequest = new OrderRequest(
                    $orderId,
                    $request->getPathInfo()
                );

                if (null !== $request->get('utm_source')) {
                    $orderRequest->setUtmSource((string)$request->get('utm_source'));
                }

                if (null !== $request->get('utm_campaign')) {
                    $orderRequest->setUtmCampaign((string)$request->get('utm_campaign'));
                }

                $this->em->persist($orderRequest);
                $this->em->flush();
            }
        }
    }
}
