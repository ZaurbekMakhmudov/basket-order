<?php


namespace App\BasketOrderBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\BasketOrderBundle\Helper\ShopConst;

/**
 * Class DeliveryClubService
 * @package App\BasketOrderBundle\BasketOrderBundle\Service
 */
class DeliveryClubService
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * DeliveryClubService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param $bodyToSZ
     * @param $addQuery
     * @return Request
     */
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

    /**
     * @param Response $responseFromSZ
     * @return array
     */
    public function getOrderResponseBodySZToDC(Response $responseFromSZ) {
        $responseFromSZBody = json_decode($responseFromSZ->getContent(), true);
        $order = $responseFromSZBody['order'];
        $items = $responseFromSZBody['items'];
        $customer = json_decode($order['customer'], 1);
        $positions = [];
        $changedOnFormed = false;
        foreach ($items as $item) {
            if(!$changedOnFormed) {
                $changedOnFormed = $item['original_quantity'] != $item['quantity'];
            }
            $positions[] = [
                'id' => $item['article'], // Идентификатор продукта
                'originalQuantity' => $item['original_quantity'], //  Кол-во исходно заказанное
                'quantity' => $item['quantity'], //  Кол-во текущее
                'formedQuantity' => $item['quantity'], // optional Кол-во в заказе текущее. Может быть дробным, если товар весовой. */
                'price' => $item['price'], // Цена продукта без скидки
                'discountPrice' => $item['cost_one_unit'],    // Цена продукта со скидкой
                'totalPrice' => $item['price'] * $item['quantity'], // Стоимость позиции без скидок
                'discountTotalPrice' => $item['cost'],    // Стоимость позиции со скидкой
                /* 'updated_date_time' => '1',  // optional */
            ];
        }
        $total = [
            'totalPrice' => $order['price'], // Стоимость заказа без скидок (без стоимости доставки)
            'discountTotalPrice' => $order['cost'], // Стоимость заказа со скидкой (без стоимости доставки)
            'deliveryPrice' => $order['delivery_cost_sum'] ?? '0',    // Стоимость доставки
        ];
        $bodyToDC = [
            'id' => $order['order_id'],
            'status' => ShopConst::getDCStatusFromStatus($order['status']),
            'expectedDateTime' => $this->getExpectedDateTime($customer),
            'positions' => $positions,
            'total' => $total,
            /* 'shortCode' => '111', // optional. Короткий код для получения заказа */
            'changedOnFormed' => $changedOnFormed,    // Вносились ли изменения при сборке заказа. Если true, то у изменившихся позиций должно быть заполнено formedQuantity.
            'createdDateTime' => $order['created'],
            /* 'startedDateTime' => 1, // optional */
            /* 'formedDateTime' => 1, // optional */
            /* 'deliveredDateTime' => 1, // optional */
            'updatedDateTime' => $order['updated'],
        ];

        return $bodyToDC;
    }

    /**
     * @param $customer
     * @return string
     */
    private function getExpectedDateTime($customer) {
        $date = $customer['desired_date'];
        $time = $customer['desired_time_from'];
        $tz = date('P');

        return $date . 'T' . $time . $tz;
    }

    /**
     * @param $expectedDateTime
     * @return array
     */
    private function parseExpectedDateTime($expectedDateTime) {
        preg_match('/(\S+)T(\S+)\+/', $expectedDateTime, $matches);

        return [
            'date' => $matches[1],
            'time' => $matches[2],
        ];
    }


    /**
     * @param Response $responseFromSZ
     * @return array
     */
    public function setStatusResponseBodySZToDC(Response $responseFromSZ) {
        $responseFromSZBody = json_decode($responseFromSZ->getContent(), true);
        $order = $responseFromSZBody['order'];
        $bodyToDC = [
            'id' => $order['order_id'],
            'status' => ShopConst::getDCStatusFromStatus($order['status']),
        ];

        return $bodyToDC;
    }

    /**
     * @param Response $responseFromSZ
     * @return array
     */
    public function createOrderResponseBodySZToDC(Response $responseFromSZ) {
        $responseFromSZBody = json_decode($responseFromSZ->getContent(), true);
        $order = $responseFromSZBody['order'];
        $bodyToDC = [
            'id' => $order['order_id'],
            'status' => ShopConst::getDCStatusFromStatus($order['status']),
        ];

        return $bodyToDC;
    }


    /**
     * @param Request $requestFromDC
     * @return array
     */
    public function setStatusRequestBodyDCToSZ(Request $requestFromDC) {
        $requestFromDCBody = json_decode($requestFromDC->getContent(), true);
        $bodyToSZ = [
            'status' => ShopConst::getStatusFromDCStatus($requestFromDCBody['status'])
        ];

        return $bodyToSZ;
    }

    /**
     * @param Request $requestFromDC
     * @param $storeId
     * @return array
     */
    public function createOrderRequestBodyDCtoSZ(Request $requestFromDC, $storeId) {
        $requestFromDCBody = json_decode($requestFromDC->getContent(), true);
        $customerDC = $requestFromDCBody['customer'];
        $deliveryDC = $requestFromDCBody['delivery'];
        $addressDC = $deliveryDC['address'];
        $cityDC = $addressDC['city'];
        $streetDC = $addressDC['street'];
        $coordinatesDC = $addressDC['coordinates'];
        $paymentDC = $requestFromDCBody['payment'];
        $positionsDC = $requestFromDCBody['positions'];
        $totalDC = $requestFromDCBody['total'];
        $comment = $requestFromDCBody['comment'] ?? null;
        $cardNumPartner = $requestFromDCBody['loyaltyCard'] ?? null;
        $orderIdPartner = $requestFromDCBody['originalOrderId'] ?? null;

        $desired = $this->parseExpectedDateTime($deliveryDC['expectedDateTime']);
        $customerSZ = [
            'city' => $cityDC['name'],
//            'post_index' => '',
            'street' => $streetDC['name'],
            'building' => $addressDC['entrance'],
            'house' => $addressDC['houseNumber'],
            'flat' => $addressDC['flatNumber'],
            'name' => $customerDC['name'],
            'phone' => $customerDC['phone'],
            'date' => $desired['date'],
            'time' => $desired['time'],
            'desired_date' => $desired['date'],
            'desired_time_from' => $desired['time'],
            'desired_time_to' => $desired['time'],
            'comment' => $comment,
        ];
/*        $logagentSZ = [
            'name' => '',
            'phone' => '',
            'email' => '',
            'date' => '',
            'time' => '',
        ];
*/
        $deliverySZ = [
            'point_id' => $storeId,
            'logagent_gln' => ShopConst::DC_SAP_ID,
//            'point_gln' => '',
//            'name' => '',
//            'phone' => '',
//            'email' => '',
//            'address' => '',
//            'cost_sum' => '',
//            'point_date' => '',
//            'logagent' => $logagentSZ,
        ];
        foreach($positionsDC as $positionDC) {
            $itemsSZ[] = [
                'name' => $positionDC['id'],
                'price' => $positionDC['price'],
                'cost' => $positionDC['discountPrice'] * $positionDC['quantity'],
                'quantity' => $positionDC['quantity'],
                'article' => $positionDC['id'],
//                'product_image_url' => $positionDC[''],
//                'weight' => $positionDC[''],
//                'volume' => $positionDC[''],
            ];
        }
        $bodyToSZ = [
            'isUseCasheBox' => false,
            'payment_type' => ShopConst::getPaymentTypeFromDC($paymentDC['type']),
            'delivery_type' => ShopConst::DELIVERY_KEY_TYPE_W12,
            'customer' => $customerSZ,
            'delivery' => $deliverySZ,
            'items' => $itemsSZ,
            'card_num_partner' => $cardNumPartner,
            'order_id_partner' => $orderIdPartner,
            'delivery_cost_sum_partner' => $totalDC['deliveryPrice'],
        ];

        return $bodyToSZ;
    }
}