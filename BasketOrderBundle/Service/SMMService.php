<?php


namespace App\BasketOrderBundle\Service;

use App\BasketOrderBundle\Entity\PartnerItemData;
use App\BasketOrderBundle\Entity\PartnerOrderData;
use App\BasketOrderBundle\Helper\SMMConst;
use App\CashboxBundle\Processor\CashboxSaveProcessor;
use App\MessageControllerBundle\MessageControllerBundle;
use App\MessageControllerBundle\Service\GlobalEventService;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\BasketOrderBundle\Helper\ShopConst;
use Symfony\Component\Security\Core\User\UserInterface;


/**
 * Class SberMegaMarketService
 * @package App\BasketOrderBundle\BasketOrderBundle\Service
 */
class SMMService
{

    /**
     * @var LogService
     */
    protected LogService $logService;


    /**
     * @var MessageControllerBundle
     */
    public $messageController;
    protected $eventParams;
    private BaseService $baseService;

    public function __construct(
        BaseService $baseService
    )
    {
        $this->baseService = $baseService;
    }

    public function setVars(
        $messageController,
        $logService,
        $eventParams
    )
    {
        $this->messageController = $messageController;
        $this->logService = $logService;
        $this->eventParams = $eventParams;
    }


    public string $method;

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

    public function prepareItemsToSZ($shipment)
    {
        $items = [];
        foreach ($shipment['items'] as $item) {
            if(isset($items[$item['offerId']])){
                $items[$item['offerId']]['quantity']++;
                $items[$item['offerId']]['cost'] += $item['finalPrice'];
            } else {
                $items[$item['offerId']] = [
                    'name' => $item['itemName'],
                    'price' => $item['price'],
                    'cost' => $item['finalPrice'],
                    'quantity' => $item['quantity'],
                    'article' => $item['offerId'],
                    'discounts' => $item['discounts']
                ];
            }
        }
        return $items;
    }

    public function setStatusResponseBodySZToSMM(Response $responseFromSZ) {
        $responseFromSZBody = json_decode($responseFromSZ->getContent(), true);
        $order = $responseFromSZBody['order'];
        $bodyToSMM = [
            'id' => $order['order_id'],
            'status' => ShopConst::getDCStatusFromStatus($order['status']),
        ];

        return $bodyToSMM;
    }

    /**
     * Проверяет есть ли обязательные данные для создания заказа
     * @param $shipment
     * @return bool
     */
    public function validateOrderNewProperties($shipment): bool
    {
        if(!isset($shipment['shipmentId']) || mb_strlen($shipment['shipmentId']) == 0) return false;
        if(!isset($shipment['handover']['deliveryId']) || mb_strlen($shipment['handover']['deliveryId']) == 0) return false;
        foreach ($shipment['items'] as $item)
        {
            if(!isset($item['goodsId']) || mb_strlen($item['goodsId']) == 0) return false;
            if(!isset($item['finalPrice']) || mb_strlen($item['finalPrice']) == 0) return false;
        }
        return true;
    }

    /**
     * Проверяет предоплачен ли заказ
     * @param $shipment
     * @return bool
     */
    public function isPrepaid($shipment): bool
    {
        if($this->getCost($shipment['items']) == $shipment['handover']['depositedAmount'])
            return true;
        return false;
    }

    public function validateOrderCancelProperties($shipment): bool
    {
        if(!isset($shipment['shipmentId']) || mb_strlen($shipment['shipmentId']) == 0) return false;
        foreach($shipment['items'] as $item){
            return true;
            if(!isset($item['goodsId']) || mb_strlen($item['goodsId']) == 0) return false;
            if(!isset($item['itemIndex']) || mb_strlen($item['itemIndex']) == 0) return false;
            if(!isset($item['offerId']) || mb_strlen($item['offerId']) == 0) return false;
        }
        return true;
    }

    /**
     * Проверяет равна ли итоговая сумма цене с вычетом скидок
     */
    public function sumIsValid($items)
    {
        foreach ($items as $item){
            $discountAmount = 0;
            foreach ($item['discounts'] as $discount){
                $discountAmount += $discount['discountAmount'];
            }
            if($item['finalPrice'] != ($item['price'] - $discountAmount)){
                return false;
            }
        }
        return true;
    }


    public function getCustomerCity($address)
    {
        if(mb_strlen($address) > 0) {
            $address = explode(',', $address);
            return $address[0];
        }
    }

    public function createOrderRequestBodySMMtoSZ($request):array
    {
        $customer = $request['customer'];
        $comment = $request['customer']['access']['comment'] ?? null;
        $positions = $request['items'];
        $city = $this->getCustomerCity($customer['address']['source']) ?? 'unknown';
        $customer = [
            'name' => $customer['customerFullName'],
            'phone' => $customer['phone'],
            'comment' => $comment,
            'city' => $city
        ];
        foreach($positions as $position) {
            $itemsSZ[] = [
                'name' => $position['name'],
                'price' => $position['price'],
                'cost' => $position['cost'],
                'quantity' => $position['quantity'],
                'article' => $position['article'],
                'discounts' => $position['discounts']
            ];
        }
        $deliverySZ = [
            'point_id' => $request['handover']['outletId'],
        ];
        $bodyToSZ = [
            'isUseCasheBox' => false,
            'payment_type' => SMMConst::PAYMENT_TYPE,
            'delivery_type' => SMMConst::DELIVERY_TYPE,
            'customer' => $customer,
            'delivery' => $deliverySZ,
            'items' => $itemsSZ,
            'card_num_partner' => [],
            'order_id_partner' => $request['handover']['deliveryId'],
            'delivery_cost_sum_partner' => [],
        ];
        return $bodyToSZ;
    }

    public function getPartnerData($request)
    {
        $requestShipmentId = $requestItemId = 1;

        foreach ($request['data']['shipments'] as $shipmentRequest){
            $partnerData[$requestShipmentId]['shipment']['shipmentId'] = $shipmentRequest['shipmentId'];
            $partnerData[$requestShipmentId]['shipment']['shipmentDate'] = $shipmentRequest['shipmentDate'];

            $handoverRequest = $shipmentRequest['handover'];

            $partnerData[$requestShipmentId]['handover']['packingDate'] = $handoverRequest['packingDate']?? null;
            $partnerData[$requestShipmentId]['handover']['reserveExpirationDate'] = $handoverRequest['reserveExpirationDate']?? null;
            $partnerData[$requestShipmentId]['handover']['outletId'] = $handoverRequest['outletId']?? null;
            $partnerData[$requestShipmentId]['handover']['serviceScheme'] = $handoverRequest['serviceScheme']?? null;
            $partnerData[$requestShipmentId]['handover']['depositedAmount'] = $handoverRequest['depositedAmount']?? null;
            $partnerData[$requestShipmentId]['handover']['deliveryInterval'] = $handoverRequest['deliveryInterval']?? null;
            $partnerData[$requestShipmentId]['handover']['deliveryId'] = $handoverRequest['deliveryId']?? null;

            $customerRequest = $shipmentRequest['customer'];

            $partnerData[$requestShipmentId]['customer']['customerFullName'] = $customerRequest['customerFullName']?? null;
            $partnerData[$requestShipmentId]['customer']['phone'] = $customerRequest['phone']?? null;
            $partnerData[$requestShipmentId]['customer']['email'] = $customerRequest['email']?? null;
            $partnerData[$requestShipmentId]['customer']['source'] = $customerRequest['address']['source']?? null;
            $partnerData[$requestShipmentId]['customer']['postalCode'] = $customerRequest['address']['postalCode']?? null;
            $partnerData[$requestShipmentId]['customer']['regionId'] = $customerRequest['address']['fias']['regionId']?? null;
            $partnerData[$requestShipmentId]['customer']['destination'] = $customerRequest['address']['fias']['destination']?? null;
            $partnerData[$requestShipmentId]['customer']['lat'] = $customerRequest['address']['geo']['lat']?? null;
            $partnerData[$requestShipmentId]['customer']['lon'] = $customerRequest['address']['geo']['lon']?? null;
            $partnerData[$requestShipmentId]['customer']['detachedHouse'] = $customerRequest['address']['access']['detachedHouse']?? null;
            $partnerData[$requestShipmentId]['customer']['entrance'] = $customerRequest['address']['access']['entrance']?? null;
            $partnerData[$requestShipmentId]['customer']['floor'] = $customerRequest['address']['access']['floor']?? null;
            $partnerData[$requestShipmentId]['customer']['intercom'] = $customerRequest['address']['access']['intercom']?? null;
            $partnerData[$requestShipmentId]['customer']['cargoElevator'] = $customerRequest['address']['access']['cargoElevator']?? null;
            $partnerData[$requestShipmentId]['customer']['comment'] = $customerRequest['address']['access']['comment']?? null;
            $partnerData[$requestShipmentId]['customer']['apartment'] = $customerRequest['address']['access']['apartment']?? null;


            foreach ($shipmentRequest['items'] as $itemRequest){
                $partnerData[$requestShipmentId]['items'][$requestItemId]['itemIndex'] = $itemRequest['itemIndex'];
                $partnerData[$requestShipmentId]['items'][$requestItemId]['goodsId'] = $itemRequest['goodsId'];
                $partnerData[$requestShipmentId]['items'][$requestItemId]['offerId'] = $itemRequest['offerId'];
                $partnerData[$requestShipmentId]['items'][$requestItemId]['itemName'] = $itemRequest['itemName'];
                $partnerData[$requestShipmentId]['items'][$requestItemId]['price'] = $itemRequest['price'];
                $partnerData[$requestShipmentId]['items'][$requestItemId]['finalPrice'] = $itemRequest['finalPrice'];
                $partnerData[$requestShipmentId]['items'][$requestItemId]['quantity'] = $itemRequest['quantity'];
                $partnerData[$requestShipmentId]['items'][$requestItemId]['taxRate'] = $itemRequest['taxRate'];
                $partnerData[$requestShipmentId]['items'][$requestItemId]['reservationPerformed'] = $itemRequest['reservationPerformed'];
                $partnerData[$requestShipmentId]['items'][$requestItemId]['isDigitalMarkRequired'] = $itemRequest['isDigitalMarkRequired'];
                $requestItemId++;
            }

            foreach ($shipmentRequest['items'] as $itemRequest){
                foreach ($itemRequest['discounts'] as $discountRequest){
                    $partnerData[$requestShipmentId][$itemRequest['itemIndex']]['discounts']['discountType'] = $discountRequest['discountType'];
                    $partnerData[$requestShipmentId][$itemRequest['itemIndex']]['discounts']["discountDescription"] = $discountRequest["discountDescription"];
                    $partnerData[$requestShipmentId][$itemRequest['itemIndex']]['discounts']['discountAmount'] = $discountRequest['discountAmount'];
                }
            }

            $requestItemId = 1;
            $requestShipmentId++;
        }
        return $partnerData;
    }


    public function writeToPartnerItemData($items, $itemId, $entityManager, $order)
    {
        foreach ($items as $key => $value) {
            if ($value === null)
                $value = 'null';
            $partnerItemData = new PartnerItemData();
            $partnerItemData->setPropertyTitle($key);
            $partnerItemData->setValue($value);
            $partnerItemData->setOrderId($order->getOrderId());
            $partnerItemData->setPartnerOrderId($order->getOrderIdPartner());
            $partnerItemData->setPartnerSapId(SMMConst::SMM_SAP_ID);
            $partnerItemData->setItemId($itemId);
            $entityManager->persist($partnerItemData);
            $entityManager->flush();
        }
    }

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
            $partnerOrderData->setPartnerSapId(SMMConst::SMM_SAP_ID);
            $entityManager->persist($partnerOrderData);
            $entityManager->flush();
        }
    }



    /**
     * Возвращает статус отмены
     * @return array
     */
    public function setCancelStatus():array {
        $bodyToSZ = [
            'status' => ShopConst::getStatusFromSMMStatus('canceled')
        ];

        return $bodyToSZ;
    }

    public function getAcceptStatus():array {
        $bodyToSZ = [
            'status' => ShopConst::getStatusFromSMMStatus('accepted')
        ];

        return $bodyToSZ;
    }

    public function setCloseStatus():array {
        $bodyToSZ = [
            /*'status' => ShopConst::getStatusFromSMMStatus('delivered')*/
            'status' => ShopConst::getStatusFromSMMStatus('delivered')
        ];
        return $bodyToSZ;
    }

    /**
     * Метод по статусу заказа определяет нужно ли что-то отправлять СММ
     * @param $orderStatus
     * @return bool|void
     */
    public function getMethod($orderStatus)
    {
        foreach(SMMConst::METHOD as $status => $method){
            if($status == $orderStatus){
                return $method;
            }
        }
        return false;
    }

    public function getCost($items)
    {
        $cost = 0;
        foreach ($items as $item)
        {
            $cost += $item['finalPrice'];
        }
        return $cost;
    }

    public function getSuccessResponse()
    {
        $response = new Response(json_encode(['success' => 1,'meta' => ['source' => 'УлыбкаРадуги']]));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function getSuccessArray()
    {
        return ['success' => 1, 'meta' => ['source' => 'УлыбкаРадуги']];
    }

    public function getErrorResponse($message = "")
    {
        $response = new Response(json_encode(['success' => 0,'meta' => ['source' => 'УлыбкаРадуги']]));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function getErrorArray()
    {
        return ['success' => 0, 'meta' => ['source' => 'УлыбкаРадуги']];
    }

    public function setPartnerData($shipments, $entityManager, $order)
    {
        foreach ($shipments as $shipment) {
            $this->writeToPartnerOrderData($shipment['shipment'], $entityManager, $order);
            $this->writeToPartnerOrderData($shipment['handover'], $entityManager, $order);
            $this->writeToPartnerOrderData($shipment['customer'], $entityManager, $order);
            foreach ($shipment['items'] as $item){
                $this->writeToPartnerItemData($item, $item['itemIndex'], $entityManager, $order);
                if(isset($shipment[$item['itemIndex']])) {
                    foreach ($shipment[$item['itemIndex']] as $discount){
                        $this->writeToPartnerItemData($discount, $item['itemIndex'], $entityManager, $order);
                    }
                }
            }
        }
    }

    /**
     * Метод срабатывает при статусе RCS, отправляет Packing на все товары
     * @param $order
     * @param $itemsData
     */
    public function allPacking($order, $itemsData, $token, $entityManager)
    {
        $request = $this->prepareDataToRabbitMQ($order, $token, $entityManager);
        $request['request_data']['data']['shipments'][0]['orderCode'] = $order->getOrderId();
        $i = 0;
        foreach ($itemsData as $itemData) {
            $this->setStockInfo($order->getOrderIdPartner(), $itemData['itemIndex'], $entityManager, 1);
            $items[$i]['itemIndex'] = $itemData['itemIndex'];
            $items[$i]['quantity'] = $itemData['quantity'];
            $i++;
        }
        $request['request_data']['data']['shipments'][0]['items'] = $items;
        $this->sendToRabbitMQ($order, $request, __METHOD__);
    }

    /**
     * Метод срабатывает при статусе PFD, отправляет Reject на все товары
     * @param $order
     * @param $itemsData
     */
    public function allReject($order, $itemsData, $token, $entityManager)
    {
        $request = $this->prepareDataToRabbitMQ($order, $token, $entityManager);
        $request['request_data']['data']['shipments'][0]['orderCode'] = $order->getOrderId();
        $i = 0;
        foreach($itemsData as $itemData){
            $this->setStockInfo($order->getOrderIdPartner(), $itemData['itemIndex'], $entityManager, 0);
            $items[$i]['itemIndex'] = $itemData['itemIndex'];
            $items[$i]['offerId'] = $itemData['offerId'];
            $i++;
        }
        $request['request_data']['data']['shipments'][0]['items'] = $items;

        $this->sendToRabbitMQ($order, $request, __METHOD__);
    }

    public function cancelResult($order, $itemsData, $token, $entityManager)
    {
        $request = $this->prepareDataToRabbitMQ($order, $token, $entityManager);
        $request['request_data']['data']['shipments'][0]['orderCode'] = $order->getOrderId();
        $i = 0;
        foreach ($itemsData as $itemData) {
            $items[$i]['itemIndex'] = $itemData['itemIndex'];
            $items[$i]['canceled'] = true;
            $i++;
        }
        $request['request_data']['data']['shipments'][0]['items'] = $items;

        $this->sendToRabbitMQ($order, $request, __METHOD__);
    }

    /**
     * Подготовка скелета запроса.
     * @param $order
     * @param $token
     * @return array
     */
    public function prepareDataToRabbitMQ($order, $token, $entityManager)
    {
        $shipmentId = $entityManager
            ->getRepository(PartnerOrderData::class)
            ->findOneBy(['partnerOrderId' => $order->getOrderIdPartner(), 'propertyTitle' => 'shipmentId']);
        $shipmentId = $shipmentId->getValue();
        return $request = ["request_data" => ["meta" => (object)[], "data" => ["token" => $token, "shipments" => [["shipmentId" => $shipmentId, "items" => [],]]]], "method" => "POST"];
    }

    public function inStock($partnerOrderId, $itemId, $entityManager)
    {
        $itemPartnerData = $entityManager
            ->getRepository(PartnerItemData::class)
            ->findBy(['partnerOrderId' => $partnerOrderId, 'partnerSapId' => SMMConst::SMM_SAP_ID, 'itemId' => $itemId]);
        foreach ($itemPartnerData as $itemData) {
            return $itemData->getInStock();
        }
    }

    public function setStockInfo($partnerOrderId, $itemId, $entityManager, $value)
    {
        $itemPartnerData = $entityManager
            ->getRepository(PartnerItemData::class)
            ->findBy(['partnerOrderId' => $partnerOrderId, 'partnerSapId' => SMMConst::SMM_SAP_ID, 'itemId' => $itemId]);
        foreach ($itemPartnerData as $partnerItem) {
            $partnerItem->setInStock($value);
            $entityManager->persist($partnerItem);
            $entityManager->flush();
        }
    }

    public function getPartnerItems($entityManager, $order)
    {
        $partnerOrder = $entityManager
            ->getRepository(PartnerOrderData::class)
            ->findBy(['orderId' => $order->getId()]);
        $partnerItemsMaxIndex = $entityManager
            ->getRepository(PartnerItemData::class)
            ->findMaxIndex($order->getOrderIdPartner());
        $maxIndex = $partnerItemsMaxIndex[0]['maxId'];
        $items = [];
        for ($i = 1; $i <= $maxIndex; $i++) {
            $partnerItems = $entityManager
                ->getRepository(PartnerItemData::class)
                ->findBy(['partnerOrderId' => $order->getOrderIdPartner(), 'itemId' => $i]);
            foreach ($partnerItems as $partnerItem) {
                $items[$i][$partnerItem->getPropertyTitle()] = $partnerItem->getValue();
            }
        }
        return $items;
    }

    /**
     * @param $shipmentId
     * @param $entityManager
     * @return false|integer
     */
    public function getDeliveryIdByShipmentId($shipmentId, $entityManager)
    {
        $deliveryId = $entityManager
            ->getRepository(PartnerOrderData::class)
            ->findOneBy(['propertyTitle' => 'shipmentId', 'value' => $shipmentId, 'partnerSapId' => SMMConst::SMM_SAP_ID]);
        if(!$deliveryId)
            return false;
        return $deliveryId->getPartnerOrderId();
    }

    /**
     * Метод отмены заказа на нашей стороне
     * @param $order
     * @param $itemsData
     */
    public function closeBySell($order, $itemsData, $token, $entityManager)
    {
        $request = $this->prepareDataToRabbitMQ($order, $token, $entityManager);
        $request['request_data']['data']['shipments'][0]['closeDate'] = $order->getUpdated()->format('Y-m-dTH:i:sP');
        $i = 0;
        foreach ($itemsData as $itemData) {
            $items[$i]['itemIndex'] = $itemData['itemIndex'];
            if($this->inStock($order->getOrderIdPartner(), $itemData['itemIndex'], $entityManager)) {
                $items[$i]['handoverResult'] = 'TRUE';
            } else {
                $items[$i]['handoverResult'] = 'FALSE';
            }
            $i++;
        }
        $request['request_data']['data']['shipments'][0]['items'] = $items;

        $this->sendToRabbitMQ($order, $request, __METHOD__);
    }

    public function closeByCustomer($order, $itemsData, $token, $entityManager)
    {
        $request = $this->prepareDataToRabbitMQ($order, $token, $entityManager);
        $request['request_data']['data']['shipments'][0]['closeDate'] = $order->getUpdated()->format('Y-m-dTH:i:sP');
        $i = 0;
        foreach ($itemsData as $itemData) {
            $items[$i]['itemIndex'] = $itemData['itemIndex'];
            $items[$i]['handoverResult'] = 'FALSE';
            $items[$i]['reason'] = ['type' => 'CANCEL_BY_CUSTOMER', 'comment' => 'Отказ покупателя при выдаче'];
            $i++;
        }
        $request['request_data']['data']['shipments'][0]['items'] = $items;
        $this->sendToRabbitMQ($order, $request, __METHOD__);
    }


    /**
     * Определяет какие товары отправлять на packing, а какие на reject
     * @param $order
     * @param $request
     * @param $itemsData
     * @param $token
     * @param $entityManager
     */
    public function partialPacking($order, $request, $itemsData, $token, $entityManager)
    {
          $items = $request['items'];
        foreach ($items as $item) {
            $partnerItems = $entityManager
                ->getRepository(PartnerItemData::class)
                ->findBy(['value' => $item['article'], 'propertyTitle' => 'offerId', 'partnerSapId' => SMMConst::SMM_SAP_ID, 'partnerOrderId' => $order->getOrderIdPartner()]);
            $partnerItemQuantity = 0;
            $itemIndex = [];
            foreach ($partnerItems as $partnerItem) {
                $partnerItemQuantity++;
                array_push($itemIndex, $partnerItem->getItemId());
            }
            if ($partnerItemQuantity == $item['quantity'] || $partnerItemQuantity < $item['quantity']) {
                for ($i = 0; $i < $partnerItemQuantity; $i++) {
                    $this->sendItemToPacking($order, $itemIndex[count($itemIndex) - 1], $itemsData, $token, $entityManager);
                    array_splice($itemIndex, count($itemIndex) - 1);
                }
            } elseif ($partnerItemQuantity > $item['quantity']) {
                if ($item['quantity'] == 0) {
                    for ($i = 0; $i < $partnerItemQuantity; $i++) {
                        $this->sendItemToReject($order, $itemIndex[count($itemIndex) - 1], $itemsData, $token, $entityManager);
                        array_splice($itemIndex, count($itemIndex) - 1);
                    }
                } else {
                    for ($i = 0; $i < $partnerItemQuantity - $item['quantity']; $i++) {
                        $this->sendItemToReject($order, $itemIndex[count($itemIndex) - 1], $itemsData, $token, $entityManager);
                        array_splice($itemIndex, count($itemIndex) - 1);
                    }
                    for ($i = 0; $i < $item['quantity']; $i++) {
                        $this->sendItemToPacking($order, $itemIndex[count($itemIndex) - 1], $itemsData, $token, $entityManager);
                        array_splice($itemIndex, count($itemIndex) - 1);
                    }
                }
            }
        }
    }

    /**
     * Отправляет один товар на packing
     * @param $order
     * @param $itemIndex
     * @param $itemsData
     * @param $token
     */
    public function sendItemToPacking($order, $itemIndex, $itemsData, $token, $entityManager)
    {
        $request = $this->prepareDataToRabbitMQ($order, $token, $entityManager);
        $request['request_data']['data']['shipments'][0]['orderCode'] = $order->getOrderId();
        $items = [];


        foreach ($itemsData as $itemData) {
            if ($itemData['itemIndex'] == $itemIndex) {
                $this->setStockInfo($order->getOrderIdPartner(), $itemIndex, $entityManager, 1);
                $items[0]['itemIndex'] = $itemIndex;
                $items[0]['offerId'] = $itemData['offerId'];
            }
        }
        $request['request_data']['data']['shipments'][0]['items'] = $items;
        $this->sendToRabbitMQ($order, $request, __METHOD__);
    }

    /**
     * Отправляет один товар на reject
     * @param $order
     * @param $itemIndex
     * @param $itemsData
     * @param $token
     */
    public function sendItemToReject($order, $itemIndex, $itemsData, $token, $entityManager)
    {
        $request = $this->prepareDataToRabbitMQ($order, $token, $entityManager);
        $request['request_data']['data']['shipments'][0]['orderCode'] = $order->getOrderId();
        $items = [];
        foreach ($itemsData as $itemData) {
            if ($itemData['itemIndex'] == $itemIndex) {
                $this->setStockInfo($order->getOrderIdPartner(), $itemIndex, $entityManager, 0);
                $items[0]['itemIndex'] = $itemIndex;
                $items[0]['offerId'] = $itemData['offerId'];
            }
        }
        $request['request_data']['data']['shipments'][0]['items'] = $items;

        $this->sendToRabbitMQ($order, $request, __METHOD__);
    }


    public function sendToRabbitMQ($order, $request, $method)
    {
        $this->logService->create(__METHOD__, $request);
        $this->baseService->makeEvent($order, $request, $this->eventParams[SMMConst::Events[$method]]);
    }

}


