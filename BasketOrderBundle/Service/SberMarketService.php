<?php

namespace App\BasketOrderBundle\Service;

use App\BasketOrderBundle\Entity\Basket;
use App\BasketOrderBundle\Entity\Item;
use App\BasketOrderBundle\Entity\Order;
use App\BasketOrderBundle\Helper\ItemHelper;
use App\BasketOrderBundle\Helper\SberMarketConst;
use Doctrine\ORM\EntityManager;
use Proxies\__CG__\App\BasketOrderBundle\Entity\PartnerItemData;
use Proxies\__CG__\App\BasketOrderBundle\Entity\PartnerOrderData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WebPlatform\InGatewayBundle\Communicator\Communicator;

class SberMarketService
{
    protected EntityManager $entityManager;
    protected OrderService $orderService;

    public function setVars(
        EntityManager $entityManager,
        OrderService $orderService)
    {
        $this->entityManager = $entityManager;
        $this->orderService = $orderService;
    }

    public function prepareRequestToSZ($bodyToSZ, $addQuery = []) {
        $requestSZ = new Request();
        $requestSZ->initialize(
            $requestSZ->query->all(),
            $requestSZ->request->all(),
            $requestSZ->attributes->all(),
            $requestSZ->cookies->all(),
            $requestSZ->files->all(),
            $requestSZ->server->all(),
            json_encode($bodyToSZ)
        );
        if(count($addQuery)>0) {
            $requestSZ->query->add($addQuery);
        }

        return $requestSZ;
    }


    public function createOrderRequestBodySberMarketToSZ($request): array
    {
        $positions = $request['payload']['positions'];
        if(isset($request['payload']['customer'])) {
            $customer = [
                'name' => $request['payload']['customer']['name'] ?? null,
                'phone' => $request['payload']['customer']['phone'] ?? null,
                'comment' => $request['payload']['comment'] ?? null,
                'city' => 'unknown'
            ];
        } else {
            $customer = [
                'name' => 'sbermarket',
                'phone' => '89000000000',
                'comment' => '',
                'city' => 'unknown'
            ];
        }
        foreach($positions as $position) {
            if(mb_strlen($position['replacedByID'] > 0))
                $article = $position['replacedByID'];
            else
                $article = $position['id'];
            $itemsSZ[] = [
                'price'    => $position['price'],
                'cost'     => $position['price'] * $position['quantity'],
                'quantity' => $position['quantity'],
                'article'  => $article,
                'lables'   => $this->markingCodePrepareToSZ($position['markingCode'] ?? null),
            ];
        }
        $deliverySZ = [
            'point_id' => $request['payload']['store_id'],
        ];
        $bodyToSZ = [
            'isUseCasheBox' => false,
            'payment_type' => SberMarketConst::PAYMENT_TYPE,
            'delivery_type' => SberMarketConst::DELIVERY_TYPE,
            'customer' => $customer,
            'delivery' => $deliverySZ,
            'items' => $itemsSZ,
            'card_num_partner' => [],
            'order_id_partner' => $request['payload']['originalOrderId'],
            'delivery_cost_sum_partner' => [],
        ];
        return $bodyToSZ;
    }

    public function getAcceptStatus():array {
        $bodyToSZ = [
            'status' => SberMarketConst::STATUS['accepted']
        ];

        return $bodyToSZ;
    }

    public function getCancelStatus():array
    {
        $bodyToSZ = [
            'status' => SberMarketConst::STATUS['canceled']
        ];

        return $bodyToSZ;
    }

    public function getDeliveredStatus():array
    {
        $bodyToSZ = [
            'status' => SberMarketConst::STATUS['delivered']
        ];

        return $bodyToSZ;
    }


    /**
     * Возвращает тип ивента который нужно обработать
     */
    public function getEventType($request)
    {
        foreach (SberMarketConst::EVENTS as $key => $value) {
            if($request['event_type'] == $key)
                return [
                    'method' => $value,
                    'description' => SberMarketConst::EVENTS_DESCRIPTION[$key]
                ];
        }
        return false;
    }


    // Партнерские данные

    /*
     * Переводит данные партнера из запроса в удобную для записи форму
     */
    public function getPartnerData($request)
    {
        $itemIndex = 0;
        foreach ($request['payload']['positions'] as $item) {
            foreach ($item as $key => $value) {
                $partnerData['items'][$itemIndex][$key] = $value;
            }
            $itemIndex++;
        }
        $paymentIndex = 0;
        foreach ($request['payload']['paymentMethods'] as $method) {
            $partnerData['paymentMethods'][$paymentIndex] = $method;
            $paymentIndex++;
        }


        $partnerData['payload'] = [
            "originalOrderId" => $request['payload']['originalOrderId'],
            "store_id" => $request['payload']['originalOrderId'],
            'comment' => $request['payload']['comment'] ?? null,
            'replacementPolicy' => $request['payload']['replacementPolicy'] ?? null,
            'shippingMethod' => $request['payload']['shippingMethod'] ?? null
        ];
        if(isset($request['payload']['customer'])) {
            $partnerData['customer'] = [
                'phone' => $request['payload']['customer']['phone'] ?? null,
                'name' => $request['payload']['customer']['name'] ?? null
            ];
        } else {
            $partnerData['customer'] = [
                'phone' => '89000000000',
                'name' => 'sbermarket'
            ];
        }
        $partnerData['total'] = [
            'totalPrice' => $request['payload']['total']['totalPrice'],
            'discountTotalPrice' => $request['payload']['total']['discountTotalPrice']
        ];

        return $partnerData;
    }

    /**
     * Распределяет информацию, полученную из заказа, в методы записывающие партнерские данные в бд
     */
    public function setPartnerData($partnerData, $entityManager, $order)
    {
        $this->writeToPartnerOrderData($partnerData['payload'], $entityManager, $order);
        $this->writeToPartnerOrderData($partnerData['customer'], $entityManager, $order);
        $this->writeToPartnerOrderData($partnerData['total'], $entityManager, $order);
        $this->writeToPartnerOrderData($partnerData['paymentMethods'], $entityManager, $order);
        $itemIndex = 1;
        foreach ($partnerData['items'] as $item) {
            $this->writeToPartnerItemData($item, $itemIndex, $entityManager, $order);
            $itemIndex++;
        }
    }

    /**
     * Записывает ассоциативный массив в таблицу partner_order_data
     */
    public function writeToPartnerOrderData($items, $entityManager, $order)
    {
        foreach ($items as $key => $value) {
            if ($value === null)
                $value = 'null';
            $partnerOrderData = new PartnerOrderData();
            $partnerOrderData->setPropertyTitle($key);
            $partnerOrderData->setValue($value);
            $partnerOrderData->setOrderId($order->getOrderId());
            $partnerOrderData->setPartnerOrderId($order->getOrderIdPartner());
            $partnerOrderData->setPartnerSapId(SberMarketConst::SBERMARKET_SAP_ID);
            $entityManager->persist($partnerOrderData);
            $entityManager->flush();
        }
    }

    /**
     * Записывает ассоциативный массив в таблицу partner_item_data
     */
    public function writeToPartnerItemData($items, $itemId, $entityManager, $order)
    {
        if (isset($items['markingCode'])) {
            $markingCodes = $items['markingCode'];
        }
        foreach ($items as $key => $value) {
            if ($value === null)
                $value = 'null';
            $partnerItemData = new PartnerItemData();
            $partnerItemData->setPropertyTitle($key);
            if ($key === 'markingCode') {
                $value = array_pop($markingCodes);
                if (is_array($value)) {
                    $value = array_pop($value);
                }
            }
            $partnerItemData->setValue($value);
            $partnerItemData->setOrderId($order->getOrderId());
            $partnerItemData->setPartnerOrderId($order->getOrderIdPartner());
            $partnerItemData->setPartnerSapId(SberMarketConst::SBERMARKET_SAP_ID);
            $partnerItemData->setItemId($itemId);
            $entityManager->persist($partnerItemData);
            $entityManager->flush();
        }
    }

    public function getResponse($status, $partnerOrderId, $message = null): Response
    {
        $out = [
            'status' => $status,
            'number' => $partnerOrderId,
        ];
        $response = new Response(json_encode($out, JSON_UNESCAPED_UNICODE));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function getError($code): Response
    {
        return new Response(json_encode(SberMarketConst::ERRORS[$code], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param array|null $markingCodes
     * @return null|array
     */
    private function markingCodePrepareToSZ(?array $markingCodes): ?array
    {
        $markingCodesPrepared = null;
        if(!empty($markingCodes)) {
            foreach ($markingCodes as $markingCode) {
                $markingCodesPrepared[]['label'] = $this->orderService->prepareMarkingCode($markingCode['value']);
            }
        }

        return $markingCodesPrepared;
    }
}
