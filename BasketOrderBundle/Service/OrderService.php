<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 26.07.19
 * Time: 17:14
 */

namespace App\BasketOrderBundle\Service;

use App\BasketOrderBundle\Entity\Basket;
use App\BasketOrderBundle\Entity\Item;
use App\BasketOrderBundle\Entity\Order;
use App\BasketOrderBundle\Entity\OrderHistory;
use App\BasketOrderBundle\Era\EshopOrder;
use App\BasketOrderBundle\Era\EshopOrderPosition;
use App\BasketOrderBundle\Helper\AppHelper;
use App\BasketOrderBundle\Helper\DateTimeHelper;
use App\BasketOrderBundle\Helper\ItemHelper;
use App\BasketOrderBundle\Helper\ShopConst;
use App\BasketOrderBundle\Helper\XmlHelper;
use App\BasketOrderBundle\SwgModel\Overtime;
use App\BasketOrderBundle\Traits\_EshopServiceTrait;
use App\CashboxBundle\Entity\Receipt;
use App\CashboxBundle\Service\Cashbox\CashboxService;
use DateTime;
use Exception;
use Metaer\CurlWrapperBundle\CurlWrapper;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\User;
use WebPlatform\InGatewayBundle\Communicator\Communicator;
use Psr\Log\LoggerInterface;


/**
 * Class App\BasketOrderBundle\Service\OrderService
 * @package App\BasketOrderBundle\Service
 */
class OrderService extends BaseService
{
    use _EshopServiceTrait;
    private $orderListToEshopOrders = [];

    /**
     * OrderService constructor.
     * @param CashboxService $cashbox
     * @param DelayService $delayService
     * @param CurlWrapper $curlWrapper
     * @param LoggerInterface $logger
     * @param ManagerRegistry $doctrine
     * @param $cashboxShop
     */
    function __construct(
        CashboxService $cashbox,
        DelayService $delayService,
        CurlWrapper $curlWrapper,
        ManagerRegistry $doctrine,
        $cashboxShop
    )
    {
        parent::__construct(
            $cashbox,
            $delayService,
            $curlWrapper,
            $doctrine,
            $cashboxShop
        );
    }

    /**
     * @return $this
     */
    public function setReManagerUrlCreate()
    {
        $this->action = ShopConst::GW_ES_ORDER_CREATE;

        return $this;
    }

    /**
     * @return $this
     */
    public function setReManagerUrlStatusSet()
    {
        $this->action = ShopConst::GW_ES_ORDER_CHANGE_SOST;

        return $this;
    }

    /**
     * @return $this
     */
    public function setReManagerUrlPaymentSet()
    {
        $this->action = ShopConst::GW_ES_ORDER_PAYMENT_SET;

        return $this;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;

    }

    /**
     * @param Order $order
     * @return mixed|null|string
     */
    private function converDeliveryCode(Order $order)
    {
        $key = $order->getDeliveryType();
        $code = $order->getDeliveryPointGln();
        $deliveryType = ShopConst::findDeliveryType($key);
        if (strpos($code, ShopConst::ORDER_PATTERN_DELIVERY_POINT_GLN_EASY) !== false) {
            $deliveryType = 'E';
        }

        return $deliveryType;
    }

    /**
     * @param Order $order
     * @param $requestBody
     * @return array
     */
    private function validatePaymentDelivery(Order $order, $requestBody)
    {
        $paymentType = $requestBody['payment_type'] ?? null;
        $deliveryType = $requestBody['delivery_type'] ?? null;
        $deliveryScheme = $requestBody['delivery_scheme'] ?? null;
        ($paymentType !== null) ? $order->setPaymentType($paymentType) : null;
        ($deliveryType !== null) ? $order->setDeliveryType($deliveryType) : null;
        ($deliveryScheme !== null) ? $order->setDeliveryScheme($deliveryScheme) : null;

        $pType = $order->getPaymentType();
        $dType = $order->getDeliveryType();

        $pTypes = ['0', '1'];
        $dTypes = ['2', '1', 'E', 'W', '10'];
        if (in_array($pType, $pTypes)) {
            $pTypeBoolean = true;
        } else {
            $pTypeBoolean = false;
            $order->setPaymentType(null);
        }

        if ($dType) {
            $dTypeBoolean = true;
        } else {
            $dTypeBoolean = false;
            $order->setDeliveryType(null);
        }

        if (!$pTypeBoolean) {
            $this->errors[] = 'payment_type is not defined';
        }
        if (!$dTypeBoolean) {
            $this->errors[] = 'delivery_type is not defined';
        }

        return [$dType, $dTypeBoolean];
    }

    /**
     * @param Order $order
     * @param $requestBody
     */
    private function validatePaymentInformation(Order $order, $requestBody)
    {
        $paymentInformation = isset($requestBody) ? $requestBody : null;
        ($paymentInformation !== null) ? $order->setPaymentInformationData($paymentInformation) : null;
    }

    /**
     * @param Order $order
     * @param $requestBody
     * @param $dType
     * @param $dTypeBoolean
     */
    private function validateDeliveryDelivery(Order $order, $requestBody, $dType, $dTypeBoolean)
    {
        $delivery = $requestBody['delivery'] ?? null;
        $out = [];
        $_out = [];
        if ($delivery) {
            isset($delivery['point_id']) ? $out['point_id'] = $delivery['point_id'] : null;
            isset($delivery['point_gln']) ? $out['point_gln'] = $delivery['point_gln'] : null;
            isset($delivery['logagent_gln']) ? $out['logagent_gln'] = $delivery['logagent_gln'] : null;
            isset($delivery['name']) ? $out['name'] = $delivery['name'] : null;
            isset($delivery['phone']) ? $out['phone'] = $delivery['phone'] : null;
            isset($delivery['email']) ? $out['email'] = $delivery['email'] : null;
            isset($delivery['address']) ? $out['address'] = $delivery['address'] : null;
            isset($delivery['cost_sum']) ? $out['cost_sum'] = $delivery['cost_sum'] : null;

            $logagent = $delivery['logagent'] ?? null;
            if ($logagent) {
                isset($logagent['name']) ? $_out['name'] = $logagent['name'] : null;
                isset($logagent['phone']) ? $_out['phone'] = $logagent['phone'] : null;
                isset($logagent['email']) ? $_out['email'] = $logagent['email'] : null;
                isset($logagent['date']) ? $_out['date'] = $logagent['date'] : null;
                isset($logagent['time']) ? $_out['time'] = $logagent['time'] : null;
            }
        }
        if( !ShopConst::isDeliveryTypeRM( $order->getOrderDeliveryType() ) ) {
            $_out = $order->setDeliveryLogagentDate($_out);
        }
        $out = $order->setOrderDeliveryLogagentAdd($out);
        if ($out) {
            $order->setDeliveryData($out);
        }
        if ($_out) {
            $order->setLogagent($_out);
        }
        $point_id = $order->getDeliveryPointId();
        $point_gln = $order->getDeliveryPointGln();
        $logagent_gln = $order->getLogagentGln();
        $cost_sum = $order->getDeliveryCostSum();
        $nameDelivery = $order->getDeliveryName();
        $phoneDelivery = $order->getDeliveryPhone();
        $emailDelivery = $order->getDeliveryEmail();
        $addressDelivery = $order->getDeliveryAddress();
        $nameLogagent = $order->getDeliveryLogagentName();
        $phoneLogagent = $order->getDeliveryLogagentPhone();
        $emailLogagent = $order->getDeliveryLogagentEmail();
        $dateLogagent = $order->getDeliveryLogagentDate();
        $timeLogagent = $order->getDeliveryLogagentTime();

        $point_idBoolean = (boolean)$point_id;
        $point_glnBoolean = (boolean)$point_gln;
        $logagent_glnBoolean = (boolean)$logagent_gln;
        $cost_sumBoolean = ($cost_sum === null) ? false : true;
        $nameDeliveryBoolean = (boolean)$nameDelivery;
        $phoneDeliveryBoolean = (boolean)$phoneDelivery;
        $emailDeliveryBoolean = (boolean)$emailDelivery;
        $addressDeliveryBoolean = (boolean)$addressDelivery;
        $nameLogagentBoolean = (boolean)$nameLogagent;
        $phoneLogagentBoolean = (boolean)$phoneLogagent;
        $emailLogagentBoolean = (boolean)$emailLogagent;
        $dateLogagentBoolean = (boolean)$dateLogagent;
        $timeLogagentBoolean = (boolean)$timeLogagent;

        if ($dType == 2 or !$dTypeBoolean) {
            if (!$point_idBoolean) {
                //$isFlush = false;
                //$this->errors[] = 'point_id is not defined';
            }
            if (!$point_glnBoolean) {
                $isFlush = false;
                $this->errors[] = 'point_gln is not defined';
            }
            if (!$logagent_glnBoolean) {
                $isFlush = false;
                //$this->errors[] = 'logagent_gln is not defined';
            }
            if (!$cost_sumBoolean) {
                $isFlush = false;
                $this->errors[] = 'cost_sum is not defined';
            }
            if (!$nameDeliveryBoolean) {
                $isFlush = false;
                $this->errors[] = 'delivery name is not defined';
            }
            if (!$phoneDeliveryBoolean) {
                //$isFlush = true;
                //$this->errors[] = 'delivery phone is not defined';
            }
            if (!$emailDeliveryBoolean) {
                //$isFlush = true;
//                $this->errors[] = 'delivery email  is not defined';
            }
            if ($addressDeliveryBoolean) {
                //$isFlush = true;
                //$this->errors[] = 'delivery address is not defined';
            }
            if (!$nameLogagentBoolean) {
                $isFlush = false;
//                $this->errors[] = 'logagent name is not defined';
            }
            if ($phoneLogagentBoolean) {
                //$isFlush = true;
                //$this->errors[] = 'logagent phone is not defined';
            }
            if ($emailLogagentBoolean) {
                //$isFlush = true;
                //$this->errors[] = 'logagent email is not defined';
            }
            if ($dateLogagentBoolean) {
                //$isFlush = true;
                //$this->errors[] = 'logagent date is not defined';
            }
            if ($timeLogagentBoolean) {
                //$isFlush = true;
                //$this->errors[] = 'logagent time is not defined';
            }
        }
    }

    /**
     * @param Order $order
     * @param $requestBody
     */
    private function validateCustomerDelivery(Order $order, $requestBody)
    {
        $customer = isset($requestBody['customer']) ? $requestBody['customer'] : null;
        $out = [];
        if ($customer) {
            isset($customer['city']) ? $out['city'] = $customer['city'] : ($order->getCustomerCity() ? $out['city'] = $order->getCustomerCity() : null);
            isset($customer['post_index']) ? $out['post_index'] = $customer['post_index'] : ($order->getCustomerPostIndex() ? $out['post_index'] = $order->getCustomerPostIndex() : null);
            isset($customer['street']) ? $out['street'] = $customer['street'] : ($order->getCustomerStreet() ? $out['street'] = $order->getCustomerStreet() : null);
            isset($customer['house']) ? $out['house'] = $customer['house'] : ($order->getCustomerHouse() ? $out['house'] = $order->getCustomerHouse() : null);
            isset($customer['flat']) ? $out['flat'] = $customer['flat'] : ($order->getCustomerFlat() ? $out['flat'] = $order->getCustomerFlat() : null);
            isset($customer['name']) ? $out['name'] = $customer['name'] : ($order->getCustomerName() ? $out['name'] = $order->getCustomerName() : null);
            isset($customer['phone']) ? $out['phone'] = $customer['phone'] : ($order->getCustomerPhone() ? $out['phone'] = $order->getCustomerPhone() : null);
            isset($customer['email']) ? $out['email'] = $customer['email'] : ($order->getCustomerEmail() ? $out['email'] = $order->getCustomerEmail() : null);
            isset($customer['building']) ? $out['building'] = $customer['building'] : ($order->getCustomerBuilding() ? $out['building'] = $order->getCustomerBuilding() : null);
            isset($customer['time']) ? $out['time'] = $customer['time'] : ($order->getCustomerTime() ? $out['time'] = $order->getCustomerTime() : null);
            isset($customer['date']) ? $out['date'] = $customer['date'] : ($order->getCustomerDate() ? $out['date'] = $order->getCustomerDate()->format('Y-m-d') : null);
            isset($customer['desired_date']) ? $out['desired_date'] = $customer['desired_date'] : ($order->getCustomerDesiredDate() ? $out['desired_date'] = $order->getCustomerDesiredDate()->format('Y-m-d') : null);
            isset($customer['desired_time_from']) ? $out['desired_time_from'] = $customer['desired_time_from'] : ($order->getCustomerDesiredTimeFrom() ? $out['desired_time_from'] = $order->getCustomerDesiredTimeFrom() : null);
            isset($customer['desired_time_to']) ? $out['desired_time_to'] = $customer['desired_time_to'] : ($order->getCustomerDesiredTimeTo() ? $out['desired_time_to'] = $order->getCustomerDesiredTimeTo() : null);
            isset($customer['comment']) ? $out['comment'] = $customer['comment'] : ($order->getCustomerComment() ? $out['comment'] = $order->getCustomerComment() : null);
        }
        $out = $order->setCustomerPostIndex($out);
        if ($out) {
            $order->setCustomerData($out);
        }

        $city = $order->getCustomerCity();
        $name = $order->getCustomerName();
        $phone = $order->getCustomerPhone();
        $email = $order->getCustomerEmail();

        $cityBoolean = (boolean)$city;
        $nameBoolean = (boolean)$name;
        $phoneBoolean = (boolean)$phone;
        $emailBoolean = (boolean)$email;

        if (!$cityBoolean) {
            $this->errors[] = 'customer city is not defined';
        }
        if (!$nameBoolean) {
            $this->errors[] = 'customer name is not defined';
        }
        if (!$phoneBoolean) {
            $this->errors[] = 'customer phone is not defined';
        }
        if (!$emailBoolean) {
//            $this->errors[] = 'customer email is not defined';
        }

    }

    /**
     * @param Order $order
     * @param $requestBody
     */
    public function parserConfirm(Order $order, $requestBody)
    {
        list($dType, $dTypeBoolean) = $this->validatePaymentDelivery($order, $requestBody);
        $this->validateDeliveryDelivery($order, $requestBody, $dType, $dTypeBoolean);
        $this->validateCustomerDelivery($order, $requestBody);
        //$order->setStatusCre();
    }

    /**
     * @param Order $order
     * @param $requestBody
     */
    public function parserConfirmDelivery(Order $order, $requestBody)
    {
        list($dType, $dTypeBoolean) = $this->validatePaymentDelivery($order, $requestBody);
        $this->validateDeliveryDelivery($order, $requestBody, $dType, $dTypeBoolean);
        $this->validateCustomerDelivery($order, $requestBody);
        $this->_persist($order);
    }

    /**
     * @param Order $order
     * @param $requestBody
     */
    public function parserConfirmCustomer(Order $order, $requestBody)
    {
        $this->validatePaymentDelivery($order, $requestBody);
        $out = $order->setOrderDeliveryLogagentAdd();
        if ($out) {
            $order->setDeliveryData($out);
        }
        $this->validateCustomerDelivery($order, $requestBody);
        $this->_persist($order);
    }

    public function parserConfirmPayment(Order $order, $requestBody)
    {
        $this->validatePaymentDelivery($order, $requestBody);
    }

    public function parserConfirmPaymentInformation(Order $order, $requestBody)
    {
        $this->validatePaymentInformation($order, $requestBody);
    }

    /**
     * @return array
     */
    public function findByOrderAviable($days=null, $code=null)
    {
        $orders = $this->repoOrder->findByOrderAviable($days,$code);

        return $orders;
    }

    public function findByOrderCodes($codes)
    {
        $orders = $this->repoOrder->findByOrderCodes($codes);

        return $orders;
    }
    /**
     * @param $orderId
     * @return mixed
     */
    public function findBySentEshopOrder($orderId)
    {
        $eshopOrders = $this->repoEshoOrder->findBySentEshopOrder($orderId);

        return $eshopOrders;
    }

    public function findBySentEshopOrders($orderId)
    {
        $eshopOrders = $this->repoEshoOrder->findBySentEshopOrders($orderId);

        return $eshopOrders;
    }

    public function findBySentEshopOrderPositions($orderId, $packedId)
    {
        $eshopOrders = $this->repoEshoOrderPosition->findBySentEshopOrderPositions($orderId, $packedId);

        return $eshopOrders;
    }

    public function findBySentEshopOrderPosition($orderId, $article, $packedId)
    {
        $eshopOrder = $this->repoEshoOrderPosition->findOneBy(['order_id' => $orderId, 'product_id' => $article, 'packet_id' => $packedId]);

        return $eshopOrder;
    }

    /**
     * @param array $options
     * @return Order[]|\object[]
     */
    public function findBy($options = [])
    {
        $items = [];
        if ($options) {
            $items = $this->repoOrder->findBy($options);
        }

        return $items;
    }

    public function getOrderByPartnerOrderId($partnerOrderId, $sapId)
    {
        $order = $this->repoOrder->findOneBy(['orderIdPartner' => $partnerOrderId, 'sourceIdentifier' => $sapId]);
        if(!$order)
            return false;
        return $order;
    }

    /**
     * @param string $userId
     * @return array
     */
    public function getOrderDeliveryPoints(string $userId): array
    {
        $deliveryPoints = [];
        if($findOrderDeliveryPoints = $this->repoOrder->findOrderDeliveryPoints($userId)) {
            $deliveryPoints = array_column($findOrderDeliveryPoints, 'deliveryPointId');
        }

        return [
            'result' => Response::HTTP_OK,
            'message' => 'get order delivery points',
            'delivery_points' => $deliveryPoints,
        ];
    }

    /**
     * @param array $options
     * @return Order|null|object
     */
    public function findOneBy($options = [])
    {
        $item = null;
        if ($options) {
            $item = $this->repoOrder->findOneBy($options);
        }

        return $item;
    }

    /**
     * @return Order[]|\object[]
     */
    public function findAll()
    {
        $orders = $this->repoOrder->findBy([], ['updated' => 'desc']);
        if ($orders) {
            /** @var Order $order */
            foreach ($orders as $order) {
                $basket = $order ? $this->repoBasket->findOneBy(['orderId' => $order->getOrderId()]) : new Basket();
                $items = $basket ? $this->repoItem->findBy(['basketId' => $basket->getId()]) : [];
                $order->setItemsCount(count($items));
                $order->setBasketObject($basket);
                $order->setItemArray($items);
            }
        }

        return $orders;
    }

    /**
     * @param $page
     * @param $limit
     * @return Order[]|\object[]
     */
    public function findAllBy($page, $limit, $status = null)
    {
        $offset = ($page - 1) * $limit;
        if ($status) {
            $status = strtoupper($status);
            $where = ['status' => $status];
        } else {
            $where = [];
        }
        $orders = $this->repoOrder->findBy($where, ['updated' => 'desc'], $limit, $offset);
        if ($orders) {
            /** @var Order $order */
            foreach ($orders as $order) {
                $basket = $order ? $this->repoBasket->findOneBy(['orderId' => $order->getOrderId()]) : new Basket();
                $items = $basket ? $this->repoItem->findBy(['basketId' => $basket->getId()]) : [];
                $order->setItemsCount(count($items));
                $order->setBasketObject($basket);
                $order->setItemArray($items);
                $eCount = $this->repoEshoOrder->countRecords($order->getOrderId());
                ($eCount > 0) ? $this->addOrderListToEshopOrders($order->getOrderId()) : null;
                $order->setEShopOrdersCount($eCount);
            }
        }

        return $orders;
    }

    public function findAllByEshop($page, $limit, $status){
        $offset = ($page - 1) * $limit;
        if ($status) {
            $status = strtoupper($status);
            $where = ['status' => $status];
        } else {
            $where = [];
        }
        $out = [];
        $count = 0;
        $orders = $this->repoOrder->findBy($where, ['updated' => 'desc'], $limit, $offset);
        if ($orders) {
            /** @var Order $order */
            foreach ($orders as $order) {
                $orderId = $order->getOrderId();
                $eOrders = $this->repoEshoOrder->findBySentEshopOrders($orderId);
                if ($eOrders) {
                    /** @var EshopOrder $eOrder */
                    foreach ($eOrders as $key => $eOrder) {
                        $packedId = $eOrder->getPacketId();
                        $items = $this->repoEshoOrderPosition->findBySentEshopOrderPositions($orderId, $packedId);
                        $out[$orderId][] = [
                            'count' => count($eOrders),
                            'order' => $eOrder,
                            'items' => $items,
                        ];
                    }
                    $count ++;
                }
            }
        }
        $out = [
            'count' => $count,
            'page' => $page,
            'limit' => $limit,
            'orders' => $this->iterateOrderItems($orders),
        ];

        return $out;
    }
    /**
     * @param $orderId
     */
    private function addOrderListToEshopOrders($orderId)
    {
        $this->orderListToEshopOrders[] = $orderId;
    }

    /**
     * @return string
     */
    public function listAddOrderListToEshopOrders()
    {
        return implode(',', $this->orderListToEshopOrders);
    }

    /**
     * @param OutputInterface|null $output
     * @param Communicator $communicator
     * @param null $days
     * @param null $code
     * @return array
     * @throws Exception
     */
    public function receiveEshopStatus(OutputInterface $output = null, Communicator $communicator, $days=null, $code=null)
    {
        $output = ($output === null) ? new NullOutput() : $output;
        $this->user = new User('console', null, ['ROLE_API']);
        $this->nameLogFile = 'info_order';
        $orders = $this->findByOrderAviable($days, $code);
        if (!$orders) {
            $out = [
                'result' => Response::HTTP_NOT_FOUND,
                'message' => 'no orders for update',
            ];
            $str = AppHelper::jsonFromArray($out);
            $this->logService->create(__METHOD__, 'received_gw;' . $str );
            return $out;
        }
        $out = [
            'count' => count($orders),
            'message' => 'start calculate',
        ];
        $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
        $today = DateTimeHelper::getInstance()->getDateCurrent();
        /** @var Order $order */
        foreach ($orders as $order) {
            $change = false;
            $historyOrderId = null;
            $orderStatus = $order->getStatus();
            $orderId = $order->getOrderId();
            $deliveryType = $order->getDeliveryType();
            $output->write('order number ' . $orderId . '; order status ' . $orderStatus); //order status RCWorder number UR-19885-8110; order status PRW

            if ( $this->isOrderFinalStatus($order) ) {
                $out = [
                    'order_id' => $orderId,
                    'result' => Response::HTTP_CONFLICT,
                    'totime' => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
                    'message' => 'order status is final',
                    'status' => $orderStatus,
                    'delivery_type' => $deliveryType,
                ];
                $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
                $output->writeln(' fail, order status is final');

                continue;
            }

            if ( ShopConst::isDeliveryTypeRM($deliveryType) ) {
                $out = [
                    'order_id' => $orderId,
                    'result' => Response::HTTP_BAD_REQUEST,
                    'totime' => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
                    'message' => 'deliveryType = ' . $deliveryType,
                    'status' => $orderStatus,
                    'delivery_type' => $deliveryType,
                ];
                $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
                $output->writeln(' fail, deliveryType = ' . $deliveryType);

                continue;
            }

            $eshopOrder = $this->repoEshoOrder->findBySentEshopOrder($orderId); //findBySentEshopOrder($order->getOrderId());
            /** @var EshopOrder $eshopOrder */
            if (!$eshopOrder) {
                $out = [
                    'order_id' => $orderId,
                    'result' => Response::HTTP_NOT_FOUND,
                    'totime' => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
                    'message' => 'eshopOrder not found',
                    'status' => $orderStatus,
                ];
                $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
                $output->writeln(' fail, eshopOrder not found');

                continue;
            }
            $eshopOrderStatus =trim($eshopOrder->getOrderStatus());
            $eshopOrderId = $eshopOrder->getId();
            $eshopOrder->setProcessedByEshopDate($today);
            if($eshopOrderStatus == ShopConst::STATUS_RCW) {
                $eshopOrderDeliveryCustomerDateString = DateTimeHelper::getInstance()
                    ->getDateString(
                        $eshopOrder->getOrderDeliveryCustomerDate(),
                        'Y-m-d',
                        true);
                if($eshopOrderDeliveryCustomerDateString) {
                    $orderDelivery = $order->getDelivery();
                    $orderDelivery['point_date'] = $eshopOrderDeliveryCustomerDateString;
                    $order->setDelivery($orderDelivery);
                    $this->_persist($order);
                }
            }

            $out = [
                'order_id' => $orderId,
                'result' => Response::HTTP_PROCESSING,
                'totime' => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
                'message' => 'received-on eshop_order',
                'status' => $orderStatus,
                'eshop_order_id' => $eshopOrderId,
                'eshop_status' => $eshopOrderStatus,

            ];
            $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
            /** @var Basket $basket */
            $basket = $this->repoBasket->findOneBy(['orderId' => $orderId]);
            if (!$basket) {
                $out = [
                    'order_id' => $orderId,
                    'result' => Response::HTTP_NOT_FOUND,
                    'totime' => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
                    'message' => 'basket not found',
                    'status' => $orderStatus,
                ];
                $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
                $out['eshop_order_id'] = $eshopOrderId;
                $out['eshop_status'] = $eshopOrderStatus;
                $this->_persist($eshopOrder, 'era');
                $output->writeln(' fail, basket not found');

                continue;
            }
            if (empty($eshopOrderStatus)) {
                $out = [
                    'order_id' => $orderId,
                    'result' => Response::HTTP_PROCESSING,
                    'totime' => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
                    'message' => 'eshop status is empty, eshopOrder update',
                    'order_status' => $orderStatus,
                    'eshop_status' => $eshopOrderStatus,
                ];
                $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
                $output->writeln(' fail, eshop status is empty');
                $this->_persist($eshopOrder, 'era');

                continue;
            } elseif ( ShopConst::isMappedStatus(ShopConst::MAPPING_STATUS_RECALC, $eshopOrderStatus) ){
                $eshopOrderPacketId = $eshopOrder->getPacketId();
                $orderItems = $this->repoItem->agregateItemForReceivedGW($basket) ;  //схлопнул позиции
                if (!$orderItems) {
                    $out = [
                        'order_id' => $orderId,
                        'result' => Response::HTTP_NOT_FOUND,
                        'totime' => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
                        'message' => 'order items not found',
                        'status' => $orderStatus,
                    ];
                    $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
                    $out['eshop_order_id'] = $eshopOrderId;
                    $out['eshop_status'] = $eshopOrderStatus;
                    $this->_persist($eshopOrder, 'era');
                    $output->writeln(' fail, order items not found');

                    continue;
                }

                if ( $this->isIncomingDostavkaOnly($orderId, $eshopOrderPacketId) ) {
                    $message = 'is incoming dostavka only';
                    $this->consoleErrorToLog($order, $eshopOrder, Response::HTTP_BAD_REQUEST, $message);
                    $this->_persist($eshopOrder, 'era');
                    $output->writeln(' fail, ' . $message);

                    continue;
                }

                /** @var Item $orderItem */
                foreach ($orderItems as $orderItem) {
                    $qty = $orderItem->getQuantity();
                    $article = $orderItem->getArticle();
                    /** @var EshopOrderPosition $eshopItem */
                    $eshopItem = $this->repoEshoOrderPosition->findOneBy(['order_id' => $orderId, 'product_id' => $article, 'packet_id' => $eshopOrderPacketId]);
                    $out = [
                        'order_id' => $orderId,
                        'result' => Response::HTTP_PROCESSING,
                        'totime' => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
                        'message' => 'received-on order_item',
                        'article' => $article,
                        'qty' => $qty,
                    ];
                    $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));

                    if ($eshopItem) {
                        $amount = $eshopItem->getProductAmount();
                        $unitPrice = $eshopItem->getProductUnitPrice();
                        if ($qty != $amount) {
                            $orderItem->setQuantity($amount);
                            $orderItem->setPrice($unitPrice);
                            $orderItem->setCost($unitPrice * $amount);
                            $this->_persist($orderItem);
                            $change = true;
                        }
                        $isEshopItem = 'is there id ' . $eshopItem->getId();
                        $productId = $eshopItem->getProductId();
                        $eshopItem->setProcessedByEshopDate($today);
                    } else {
                        $orderItem->setQuantity(0);
                        $orderItem->setCost(0);
                        $this->_persist($orderItem);
                        $isEshopItem = 'not found';
                        $productId = null;
                        $amount = null;
                        $change = true;
                    }
                    $out = [
                        'order_id' => $orderId,
                        'result' => Response::HTTP_PROCESSING,
                        'totime' => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
                        'message' => 'received-on eshop_item',
                        'isEshopItem' => $isEshopItem,
                        'product_id' => $productId,
                        'amount' => $amount ,
                    ];
                    $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
                }

                if ($change) {
                    $orderStatus = $eshopOrderStatus == ShopConst::STATUS_INV ? ShopConst::STATUS_OPC : ShopConst::STATUS_SHC;
                    $basket->updateBasketPrice($orderItems, $this->costDeliveryExcludedDiscountCodes);
                    $this->updateOrderForCheckout($basket, $order);
                } else {
                    $orderStatus = $eshopOrderStatus == ShopConst::STATUS_INV ? ShopConst::STATUS_CNC : ShopConst::STATUS_SHS;
                }
                $order->setStatus($orderStatus);
                $historyOrderId = $this->insertIntoOrderHistory($order, $basket);
                //нужно схлопнут в массив полученные из кассы
                $items = ItemHelper::aggrOrderItemsArray($orderItems);
                $out = [
                    'order_id' => $orderId,
                    'result' => Response::HTTP_PROCESSING,
                    'totime' => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
                    'message' => 'order sending to eshop',
                    'items' => $items,
                ];
                $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
                $this->sendEshopOrderData($order,$basket,$items);
                $this->_persist($order);
                $this->_persist($eshopOrder, 'era');
                $this->_persist($basket);
            }elseif ($orderStatus == $eshopOrderStatus){
                $out = [
                    'order_id' => $orderId,
                    'result' => Response::HTTP_PROCESSING,
                    'totime' => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
                    'message' => 'status is equvivalent',
                    'order_status' => $orderStatus,
                    'eshop_status' => $eshopOrderStatus,
                ];
                $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
                $output->writeln(' fail, status is equvivalent');
                $this->_persist($eshopOrder, 'era');

                continue;
            }elseif ($orderStatus != $eshopOrderStatus){
                $order->setStatus($eshopOrderStatus);
                $historyOrderId = $this->insertIntoOrderHistory($order, $basket);
                $out = [
                    'order_id' => $orderId,
                    'result' => Response::HTTP_PROCESSING,
                    'totime' => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
                    'message' => 'status received from gate',
                    'order_status' => $orderStatus,
                    'eshop_status' => $eshopOrderStatus,
                ];
                $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
                $this->_persist($eshopOrder, 'era');
                $this->_persist($order);
            }else{
                $out = [
                    'order_id' => $orderId,
                    'result' => Response::HTTP_PROCESSING,
                    'totime' => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
                    'message' => 'other option',
                    'order_status' => $orderStatus,
                    'eshop_status' => $eshopOrderStatus,
                ];
                //$this->_persist($eshopOrder, 'era');
                $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));

                continue;
            }
            $this->_flush();
            $this->_flush('era');
            $out = $this->sendToCommunicator($communicator, $order);
            $this->makeEvent($order, $this->makeEventData($order, $basket), null, $historyOrderId);
            $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
            if ($result != Response::HTTP_OK) {
                //@todo
            }
            $message = 'order send communicator, ' . DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s');
            $message = 'order status update';
            $order = $this->repoOrder->findOneBy(['orderId'=>$orderId]);
            $out = [
                'order_id' => $order ? $order->getId() : $orderId,
                'result' => $result,
                'status' => $order ? $order->getStatus() : null,
                'totime' => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
                'message' => $message,
                'status_communicator' => isset($out['status_communicator']) ? $out['status_communicator'] : null,
                'body' => isset($out['body']) ? $out['body'] : null,
                'errors' => isset($out['errors']) ? $out['errors'] : null,
                'order' => $order, // ? $order->iterateVisible() : null,
            ];
            $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
            $output->writeln(' success');
        }
        $out = [
            'message' => 'finish calculate',
        ];
        $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
    }

    /**
     * @param Request $request
     * @param Order $order
     * @param Basket $basket
     * @return array|bool
     */
    public function confirmPaymentOrder(Request $request, Order $order, Basket $basket)
    {
        $out = $this->confirmOptionOrder($request, $order, $basket);

        return $out;
    }

    /**
     * @param Request $request
     * @param Order $order
     * @param Basket $basket
     * @param Communicator $communicator
     * @return array|bool
     */
    public function confirmPaymentInformationOrder(Request $request, Order $order, Basket $basket, Communicator $communicator)
    {
        $status = ShopConst::makeOrderStatusFromPaymentInformationStatus($order->getPaymentInformationStatus());
        $items = $this->repoItem->agregateItemForCashbox($basket);
        $itemsCashboxResponse = empty($basket->getCashboxResponse()) ? $items : ItemHelper::aggrOrderCashboxItemsArray( $this->getItemsFormCashboxResponse($basket) );
        $historyOrderId = $this->insertIntoOrderHistory($order, $basket, $status);
        if( ShopConst::isDeliveryTypeRM($order->getDeliveryType()) ) {
            $out = $this->setReManagerUrlPaymentSet()->sendReManagerOrder($order, $basket, $itemsCashboxResponse, $communicator, $historyOrderId);
            $result = isset($out['result']) ? $out['result'] : Response::HTTP_BAD_REQUEST;
            if ($result != Response::HTTP_OK) {
                $out = $this->makeOutError($order, $result, $out);
                $this->sendCommunicatorErrorNotify($order, $basket, $communicator, $out);
            }
        } else {
            $out = $this->sendEshopOrderData($order, $basket, $items, $status);
            $this->_flush('era');
        }
        $out = $this->confirmPaymentInformationOptionOrder($request, $order, $basket);
        $orderToEvent = clone $order;
        $orderToEvent->setStatus($status);
        $this->makeEvent($order, $this->makeEventData($orderToEvent, $basket), null, $historyOrderId);

        return $out;
    }

    /**
     * @param Request $request
     * @param Order $order
     * @param Basket $basket
     * @return array|bool
     */
    public function confirmCustomerOrder(Request $request, Order $order, Basket $basket)
    {
        $out = $this->confirmOptionOrder($request, $order, $basket);

        return $out;
    }

    /**
     * @param Request $request
     * @param Order $order
     * @param Basket $basket
     * @return array|bool
     */
    public function confirmDeliveryOrder(Request $request, Order $order, Basket $basket)
    {
        $out = $this->confirmOptionOrder($request, $order, $basket);

        return $out;
    }
    /**
     * @param Request $request
     * @param Order $order
     * @param Basket $basket
     * @return array|bool
     */
    public function confirmOptionOrder(Request $request, Order $order, Basket $basket)
    {
        list($items, $out) = $this->getOrderItems($request, $order, $basket);
        if ($out) {

            return $out;
        }
        $out = $this->isCouponForPayment($order, $basket);

        if ($out = $this->isSendCashBox($order, $basket, $out)) {

            return $out;
        }

        $out = $this->responseConfirm($order, $basket, $items);

        return $out;
    }

    /**
     * @param Request $request
     * @param Order $order
     * @param Basket $basket
     * @return array|bool
     */
    public function confirmPaymentInformationOptionOrder(Request $request, Order $order, Basket $basket)
    {
        list($items, $out) = $this->getOrderItems($request, $order, $basket);
        if ($out) {

            return $out;
        }
        $out = $this->responseConfirm($order, $basket, $items);

        return $out;
    }

    /**
     * @param Order $order
     * @param Basket $basket
     * @param $items
     * @param Communicator|null $communicator
     * @param int|null $historyOrderId
     * @return array
     */
    public function sendReManagerOrder(Order $order, Basket $basket, $items, Communicator $communicator = null, int $historyOrderId = null): array
    {
        $today = DateTimeHelper::getInstance()->getDateCurrent();
        $order->setEshopOrderData($today);
        $domDocument = XmlHelper::getDomDocument($order, $basket, $items);
        $result = $domDocument['result'] ?? Response::HTTP_BAD_REQUEST;
        if ($result != Response::HTTP_OK) {
            $str = AppHelper::jsonFromArray($domDocument);
            return $domDocument;
        }
        $xmlContent = $domDocument['xmlContent'] ?? null;
        $str = mb_convert_encoding($xmlContent, "UTF-8", 'HTML-ENTITIES');
        if (!$xmlContent) {
            $message = 'error-send_re_manager-no_xml';
            $out = [
                'result' => Response::HTTP_BAD_REQUEST,
                'error' => 'xmlContent is null',
                'message' => $message,
            ];
            $str = AppHelper::jsonFromArray($out);
            return $out;
        }

        $postData = $this->createPostData($order, $xmlContent);
        $postData = mb_convert_encoding($postData, "UTF-8", 'HTML-ENTITIES');
        $title = 'send-communicator';
        $this->delayService->initDelay($title);
        try {
            $out = $this->sendToCommunicator($communicator, $order, ShopConst::getCommunicatorScriptRM($this->action), $postData, true, $historyOrderId);
        } catch (Exception $e) {
            $this->delayService->finishDelay($basket->getId(), $title);
            $out = [
                'result' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'undefined error to send order xml communicator-rm',
                'error' => $e->getMessage(),
                'option' => json_encode($postData),
            ];
            $str = AppHelper::jsonFromArray($out);
            return $out;
        }
        $this->delayService->finishDelay($basket->getId(), $title);

        $result = !empty($out['result']) ? $out['result'] : false;
        if (!$result) {
            $out = [
                'result' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'error, no response curl data',
                'errorMsg' => isset($out['errorMsg']) ? $out['errorMsg'] : null,
                'error' => isset($out['error']) ? $out['error'] : null,
                'url' => isset($out['url']) ? $out['url'] : null,
                'port' => isset($out['port']) ? $out['port'] : null,
                'response' => isset($out['response']) ? $out['response'] : null,
                'post_data' => $str,
            ];
            $str = AppHelper::jsonFromArray($out);
            return $out;
        }
        $out = [
            'result' => Response::HTTP_OK,
            'message' => 'send data to remanager',
            'url' => isset($out['url']) ? $out['url'] : null,
            'port' => isset($out['port']) ? $out['port'] : null,
            'response' => isset($out['response']) ? $out['response'] : null,
            'post_data' => $str,
            'errorMsg' => isset($out['errorMsg']) ? $out['errorMsg'] : null,
            'error' => isset($out['error']) ? $out['error'] : null,
        ];

        $str = AppHelper::jsonFromArray($out);
        return $out;
    }

    /**
     * @param Order $order
     * @param Basket $basket
     * @param array $requestBody
     * @param Communicator $communicator
     * @return array
     */
    public function setOrderStatusRm(Order $order, Basket $basket, array $requestBody, Communicator $communicator)
    {
        $status   = ($requestBody and isset($requestBody['status']))   ? strtoupper($requestBody['status'])     : null;
        $itemData = ($requestBody and isset($requestBody['items']))    ? $requestBody['items']                  : null;
        $delivery = ($requestBody and isset($requestBody['delivery'])) ? $requestBody['delivery']               : null;
        $overtimeDate = ($requestBody and isset($requestBody['overtime_date'])) ? $requestBody['overtime_date'] : null;

        $str = 'received-on: order ' . $status
            . '/itemData ' . AppHelper::jsonFromArray($itemData)
            . '/delivery ' . AppHelper::jsonFromArray($delivery);
        $deliveryType = $order->getDeliveryType();
        if( !ShopConst::isDeliveryTypeRM($deliveryType) ) {
            $out = [
                'result' => Response::HTTP_BAD_REQUEST,
                'message' => 'delivery type should be 10',
                'order_id' => $order->getOrderId(),
            ];

            return $out;
        }
        if ($delivery != null) {
            $orderDelivery = $order->getDelivery() ?? [];
            $newDelivery = array_merge($orderDelivery, $delivery);
            $order->setDelivery($newDelivery);
        }
        if($overtimeDate != null) {
            try {
                $dt = new DateTime($overtimeDate);
                if ($dt instanceof DateTime) {
                    $order->setOvertimeDate($dt->format('Y-m-d\TH:i:s'));
                }
            } catch (Exception $e) {

            }
        }
        $logicOut = $this->overrideLogic('update-rm', $order, $basket, $communicator, ['status' => $status, 'itemData' => $itemData]);
        if($logicOut['result'] != Response::HTTP_OK) {

            return [
                'result'  => $logicOut['result'],
                'message' => $logicOut['message'],
            ];
        }
        $this->_flush();
        $out = [
            'result' => $logicOut['result'],
            'message' => 'order status updated',
            'store_id' => $basket->getStoreId(),
            'order_id' => $order->getOrderId(),
            'order' => $order,
        ];
        $this->makeEvent($order, $this->makeEventData($order, $basket), null, $logicOut['historyOrderId']);

        return $out;
    }

    /**
     * @param Order $order
     * @param Basket $basket
     * @param $itemsData
     * @param $status
     * @param Communicator $communicator
     * @return array
     * @throws Exception
     */
    protected function updateReManagerStatus(Order $order, Basket $basket,  $itemsData, $status, Communicator $communicator): array
    {
        $identifier = $order->getPaymentType() === ShopConst::PAYMENT_KEY_TYPE_C ? $basket->getSoftCheque() :  $basket->getIdentifier();
        if ($status == ShopConst::STATUS_INC) {
            $orderItems = $this->repoItem->agregateItemForRM($basket);
            if (!$orderItems) {

                return [
                    'result' => Response::HTTP_NOT_FOUND,
                    'message' => 'items for order not found',
                    'order_id' => $order->getOrderId(),
                ];
            }

            if (!is_null($identifier)) {
                $receipt = $removeReceipt = $this->cashbox->getNewReceipt($identifier);
            }

            /** @var Item $orderItem */
            $change = false;
            foreach ($orderItems as $orderItem) {
                $qty = $orderItem->getQuantity();
                $article = $orderItem->getArticle();
                $inputItems = $itemsData[$article] ?? null;
                $str = 'received-on: order_item ' . $article . '/' . $qty;
                if ($inputItems) {
                    $amount = $inputItems['quantity'] ?? null;
                    if ($qty > $amount) {
                        $change = true;
                        if (!is_null($identifier)) {
                            $removeReceipt = $this->cashbox->removeReceiptItems($receipt, $article, $amount);
                        }
                        $orderItem->setQuantity($amount);
                        $unitPrice = $orderItem->getCostOneUnit() ?? $orderItem->getPrice();
                        $cost = $unitPrice * $amount;
                        $orderItem->setCost($cost);
                    }
                    if (!is_null($identifier) && isset($inputItems['tmctype']) && isset($inputItems['excisemark'])) {
                        $tmctype = $inputItems['tmctype'];
                        $excisemark = $inputItems['excisemark'];
                        $this->addMarkCodeInItems($tmctype, $excisemark, $removeReceipt, $orderItem);
                        $receipt->setTotal(true);
                        $receipt->setContent($this->cashbox->toStringReceipt($receipt, ['res']));
                    }
                    $barcode = $inputItems['article'] ?? null;
                    $str .= ', order_eshop ' . $barcode . '/' . $amount;
                }
                $items[] = '';
            }
            $order->setStatus(ShopConst::STATUS_OPC);
            $this->setOnConfirm($order);
            if( !$this->isUseCasheBox($order) ) {
                $basket->updateBasketPrice($orderItems, $this->costDeliveryExcludedDiscountCodes);
                $this->_persist($basket);
            } else {
                $out = $this->sendCashBox($basket, $orderItems, $order, false, $removeReceipt);
                $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
                if ($result != Response::HTTP_OK) {

                    return [
                        'result' => $result,
                        'message' => $out['message'],
                        'order_id' => $order->getOrderId(),
                    ];
                }
            }
            $this->updateOrderForCheckout($basket, $order);
            $historyOrderId = $this->insertIntoOrderHistory($order, $basket);
            $this->_flush();
            $items= $this->repoItem->agregateItemForCashbox($basket);
            $itemsCashboxResponse = empty($basket->getCashboxResponse()) ? $items : ItemHelper::aggrOrderCashboxItemsArray( $this->getItemsFormCashboxResponse($basket) );
            $out = $this->sendReManagerOrder($order,$basket, $itemsCashboxResponse, $communicator, $historyOrderId);
            $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
            if ($result != Response::HTTP_OK) {
                $message = $out['message'] ?? 'undefined error on line ' . __LINE__ . ' for  method' . __METHOD__;
                return [
                    'result' => $result,
                    'message' => $message,
                    'order_id' => $order->getOrderId(),
                ];
            }
        } elseif ($status == ShopConst::STATUS_FFM) {
            $orderItems = $this->repoItem->agregateItemForRM($basket);
            if (!$orderItems) {

                return [
                    'result' => Response::HTTP_NOT_FOUND,
                    'message' => 'items for order not found',
                    'order_id' => $order->getOrderId(),
                ];
            }

            $receipt = $this->cashbox->getNewReceipt($identifier);
            foreach ($orderItems as $orderItem) {
                $qty = $orderItem->getQuantity();
                $article = $orderItem->getArticle();
                $inputItems = $itemsData[$article] ?? null;
                if ($inputItems) {
                    $amount = $inputItems['quantity'] ?? null;
                    if ($qty !== $amount) {
                        return [
                            'result' => Response::HTTP_BAD_REQUEST,
                            'message' => 'Quantity not equally',
                            'order_id' => $order->getOrderId(),
                        ];
                    }
                    if (isset($inputItems['tmctype']) && isset($inputItems['excisemark'])) {
                        $tmctype = $inputItems['tmctype'];
                        $excisemark = $inputItems['excisemark'];
                        $this->addMarkCodeInItems($tmctype, $excisemark, $receipt, $orderItem);
                        $receipt->setTotal(true);
                        $receipt->setContent($this->cashbox->toStringReceipt($receipt, ['res']));
                    }

                } else {
                    return [
                        'result' => Response::HTTP_NOT_FOUND,
                        'message' => 'Article not found',
                        'order_id' => $order->getOrderId(),
                    ];
                }
            }
            $order->setStatus($status);
            $this->setOnConfirm($order);
            if($order->getPaymentType() === ShopConst::PAYMENT_KEY_TYPE_C)
                $out = $this->sendCashBox($basket, $orderItems, $order, false, $receipt);
            else
                $out = [
                    'result' => Response::HTTP_OK,
                    'basket' => $basket->getId()
                ];
            $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
            if ($result != Response::HTTP_OK) {
                return [
                    'result' => $result,
                    'message' => $out['message'],
                    'order_id' => $order->getOrderId(),
                ];
            }

            $this->updateOrderForCheckout($basket, $order);
            $historyOrderId = $this->insertIntoOrderHistory($order, $basket);
            $this->_flush();
            $items = $this->repoItem->agregateItemForCashbox($basket);
            if($order->getPaymentType() === ShopConst::PAYMENT_KEY_TYPE_C)
                $itemsCashboxResponse = empty($basket->getCashboxResponse()) ? $items : ItemHelper::aggrOrderCashboxItemsArray($this->getItemsFormCashboxResponse($basket));
            else
                $itemsCashboxResponse = $items;
            $out = $this->sendReManagerOrder($order, $basket, $itemsCashboxResponse, $communicator, $historyOrderId);
            $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
            if ($result != Response::HTTP_OK) {
                $message = $out['message'] ?? 'undefined error on line ' . __LINE__ . ' for  method' . __METHOD__;
                return [
                    'result' => $result,
                    'message' => $message,
                    'order_id' => $order->getOrderId(),
                ];
            }
        } else {
            $order->setStatus($status);
            $historyOrderId = $this->insertIntoOrderHistory($order, $basket);
            $this->_persist($order);
        }

        return [
            'result' => Response::HTTP_OK,
            'message' => 'update status',
            'order_id' => $order->getOrderId(),
            'historyOrderId' => $historyOrderId,
        ];
    }

    private function addMarkCodeInItems(string $tmctype, array $excisemark, Receipt $receipt, Item $orderItem)
    {
        $excisemark = $this->prepareMarkingCodes($excisemark);
        $this->beginTransaction();
        try {
            $orderItem->setTmctype($tmctype);
            $orderItem->setExcisemark($excisemark);
            $positions = $receipt->getPositions();
            foreach ($positions as $item){
                /** @var \App\CashboxBundle\Entity\Item $item */
                $mark = array_pop($excisemark);
                $item->setTmctype($tmctype);
                $item->setExcisemark(json_encode([$mark], JSON_UNESCAPED_SLASHES));
            }
            $this->em->flush();
            $this->commit();
        } catch (Exception $exception) {
            $this->rollBack();
        }
    }

    /**
     * @param Request $request
     * @param Order $order
     * @param Basket $basket
     * @param Communicator $communicator
     * @return array|bool
     */
    public function confirmOrder(Request $request, Order $order, Basket $basket, Communicator $communicator)
    {
//        $this->beginTransaction();
        list($items, $out) = $this->getOrderItems($request, $order, $basket);
        if ($out) {

            return $out;
        }
        if($out = $this->isOrderErrors($order)){

            return $out;
        }
        if( $basket->isActive() && $order->getStatus() == ShopConst::STATUS_ONL ) {
            $order->setStatus(ShopConst::STATUS_DRAFT);
        }
        if ($out = $this->isStatusDraft($order)) {

            return $out;
        }
        $order->setCreated(new DateTime());
        $this->isCouponForPayment($order, $basket);
        $this->setOnConfirm($order);
        if ($out = $this->isSetStatusByPaymentType($order, $basket)) {

            return $out;
        }
        if ( $overtimeText = ShopConst::getOvertimeText($order->getDeliveryType()) ) {
            $order->setOvertimeText($overtimeText);
        }
        $this->_persist($order);
        $this->_flush();
        if ($out = $this->isSendCashBox($order, $basket, $out)) {
            $order->setStatus(ShopConst::STATUS_DRAFT);
            $this->_persist($order);
            $this->_flush();
            $historyOrderId = $this->insertIntoOrderHistory($order, $basket);
//            $this->rollBack();

            return $out;
        }
//        $this->commit();
        $historyOrderId = $this->insertIntoOrderHistory($order, $basket);
        $this->responseConfirm($order, $basket, $items);
        $out = $this->sendToCommunicatorWithDelay($order, $basket, $communicator);
        $result = $this->notifyIfError($out, $order, $basket, $communicator, 'order-update');
        $this->setBasketInActive($basket);
        $this->_flush('era');
        $this->_flush();
        $this->makeEvent($order, $this->makeEventData($order, $basket), null, $historyOrderId);

        $out['result'] = Response::HTTP_OK;
        $out['message'] = 'order confirm';
        $out['store_id'] = $basket->getStoreId();
        $this->errors ? $out['errors'] = $this->errors : null;
        $out['order'] = $order;
        $out['basket'] = $basket;
        $out['items'] = $items;

        return $out;
    }

    /**
     * @param Order $order
     * @param Basket $basket
     * @param array $requestBody
     * @param Communicator $communicator
     * @param array|null $destinations
     * @param bool $forced
     * @return array
     * @throws Exception
     */
    public function setOrderStatus(Order $order, Basket $basket, array $requestBody, Communicator $communicator, array $destinations = null, $forced = false)
    {
        $status = ($requestBody and isset($requestBody['status'])) ? strtoupper($requestBody['status']) : null;
        $logicOut = $this->overrideLogic('update', $order, $basket, $communicator, ['forced' => $forced, 'status' => $status, 'destinations' => $destinations]);
        $result = $logicOut['result'];
        if($result != Response::HTTP_OK) {

            return [
                'result' => $result,
                'message' => $logicOut['message'],
            ];
        }
        $this->_flush('era');
        $this->_flush();
        $out = [
            'result' => $result,
            'message' => 'order status updated',
            'store_id' => $basket->getStoreId(),
            'order_id' => $order->getOrderId(),
            'order' => $order,
            'basket' => $basket,
        ];
        $this->makeEvent($order, $this->makeEventData($order, $basket), null, $logicOut['historyOrderId']);

        return $out;
    }

    /**
     * @param Order $order
     * @param Basket $basket
     * @return array
     */
    public function getOrderInfo(Order $order, Basket $basket){
        $message = 'info for order';
        $result = Response::HTTP_OK;
        $items = $basket ? $this->repoItem->findBy(['basketId' => $basket->getId()]) : null;
        $out = [
            'result' => $result,
            'message' => $message,
            'store_id' => $basket->getStoreId(),
            'order' => $order,
            'basket' => $basket,
            'items' => $items,
            'coupons' => $items ? $this->getCouponsAppliedResult($basket, $items) : null,
        ];

        return $out;
    }
    public function updateOrderStatusGW(Order $order, Basket $basket, $requestBody)
    {
        $orderData = $requestBody['order'];
        $status = strtoupper($orderData['order_status']);
        $sum = $orderData['order_sum'];

        $order->setStatus($status);
        ($sum > 0) ? $order->setCost($sum) : null;
        $this->_persist($order);


        $itemsData = ItemHelper::getItemsGW($requestBody['items']);
        $items = $basket ? $this->repoItem->findBy(['basketId' => $basket->getId()]) : null;
        if($itemsData){
            if($items){
                foreach ($items as $item){
                    $article = $item->getArticle();
                    $qty = $item->getQuantity();
                    $cost = $item->getCost();
                    if(isset($itemsData[$article])){
                        $product = $itemsData[$article];
                        $amount = $product['product_amount'];
                        $unitPrice = $product['product_unit_price'] ;
                        if($amount != $qty){
                            $item->setQuantity($amount);
                            $item->setCost($amount * $unitPrice);
                            $this->_persist($item);
                        }
                    }
                }
            }
            try{
                $basket->updateBasketPrice($items, $this->costDeliveryExcludedDiscountCodes);
                $this->_persist($basket);
                $this->updateOrderForCheckout($basket);
                //$this->_flush();
            }catch (Exception $e){
                $out = [
                    'result' => Response::HTTP_BAD_REQUEST,
                    'message' => 'order no update from gw',
                    'error' => $e->getMessage(),
                    'order_id' => $order->getOrderId(),
                ];

                return $out;
            }
        }
        $out = [
            'result' => Response::HTTP_OK,
            'message' => 'order update from gw',
            'order_id' => $order->getOrderId(),
            'order' => $order, //->iterateVisible(),
            'items' => $this, //->iterateItems($items),
        ];

        return $out;
    }
    public function setOrderStatusGW(Order $order, Basket $basket, $items, $status, Communicator $communicator)
    {
        $items = ItemHelper::aggrOrderItemsArray($items);
        $order->setStatus($status);
        $out = $this->sendEshopOrderGWData($order,$basket,$items);
        $result = isset($out['result']) ? $out['result'] : Response::HTTP_BAD_REQUEST;
        if ($result != Response::HTTP_OK) {
            $message = isset($out['message']) ? $out['message'] : 'undefined error on line ' . __LINE__ . ' for  method' .  __METHOD__;
            $out = [
                'result' => $result,
                'message' => $message,
                'order_id' => $order->getOrderId(),
            ];

            return $out;
        }
        $title = 'send-communicator';
        $this->delayService->initDelay($title);
        $out = $this->sendToCommunicator($communicator, $order);
        $this->delayService->finishDelay($basket->getId(), $title);
        if ($result != Response::HTTP_OK) {
            $message = isset($out['message']) ? $out['message'] : 'undefined error on line ' . __LINE__ . ' for  method' .  __METHOD__;
            $out = [
                'result' => $result,
                'message' => $message,
                'order_id' => $order->getOrderId(),
            ];

            return $out;
        }
        $this->_flush('era');
        $this->_flush();
        $out = [
            'result' => $result,
            'message' => 'order status updated',
            'store_id' => $basket->getStoreId(),
            'order_id' => $order->getOrderId(),
            'order' => $order, //->iterateVisible(),
        ];

        return $out;
    }
    public function sendEshopOrder($requestBody, $communicator)
    {
        $output = [];
        $ordersData = ($requestBody and isset($requestBody['orders'])) ? $requestBody['orders'] : null;
        foreach ($ordersData as $orderData){
            $orderId = isset($orderData['order_id']) ? $orderData['order_id'] : null;
            if(!$orderId){
                $message = [
                    'message' => 'orderId not defined',
                ];
                continue;
            }
            $order = $this->repoOrder->findOneBy(['orderId' => $orderId]);
            if(!$order){
                $message = [
                    'message' => 'order not found',
                    'order_id' => $orderId,
                ];

                continue;
            }

            if ( $this->isOrderFinalStatus($order) ) {
                $message = [
                    'message' => 'order status is final',
                    'order_id' => $orderId,
                ];

                continue;
            }

            $basket = $this->repoBasket->findOneBy(['orderId' => $orderId]);
            if(!$basket){
                $message = [
                    'message' => 'basket not found',
                    'order_id' => $orderId,
                ];

                continue;
            }

            $items = $this->repoItem->findBy(['basketId' => $basket->getId()]);
            if(!$items){
                $message = [
                    'message' => 'items not found',
                    'basket_id' => $basket->getId(),
                ];

                continue;
            }

            isset($orderData['status']) ? $order->setStatus($orderData['status'])  : null;
            isset($orderData['payment_type']) ? $order->setPaymentType($orderData['payment_type']) : null;
            isset($orderData['price']) ? $order->setPrice($orderData['price']) : null;
            isset($orderData['cost']) ? $order->setCost($orderData['cost']) : null;
            isset($orderData['comment']) ? $order->setComment($orderData['comment']) : null;
            isset($orderData['delivery_type']) ? $order->setDeliveryType($orderData['delivery_type']) : null;

            $out = $this->setOrderStatusGW($order, $basket, $items, $order->getStatus(), $communicator);
            $result = isset($out['result']) ? $out['result'] : Response::HTTP_BAD_REQUEST;
            if ($result != Response::HTTP_OK) {
                $message = isset($out['message']) ? $out['message'] : 'undefined error on line ' . __LINE__ . ' for  method' .  __METHOD__;
                $out = [
                    'result' => $result,
                    'message' => $message,
                    'order_id' => $order->getOrderId(),
                ];
                $this->logService->create(__METHOD__, 'error-send_gw;' . AppHelper::jsonFromArray($out));

                continue;
            }

            $this->logService->create(__METHOD__, 'send_gw;' . AppHelper::jsonFromArray($out));
            $output[] = $out;
        }

        $out = [
            'result' => Response::HTTP_OK,
            'message' => 'sended to gw ' . count($output),
            'output' => $output,
        ];

        return $out;
    }

    /**
     * @param OutputInterface $output
     * @param $content
     */
    public function receiveEshopDump(OutputInterface $output, $content)
    {
        $data = AppHelper::arrayFromJson($content);
        $orders = isset($data['orders']) ? $data['orders'] : null;
        if($orders){
            foreach ($orders as $key=>$datas){
                $output->writeln('заказ ' . $key);
                foreach ($datas as $data){
                    $order = $data['order'];
                    $items = $data['items'];
                    if($items){
                        foreach ($items as $item){
                            try{
                                $p = $this->repoEshoOrderPosition->findOneBy(['order_id'=>$item['order_id'], 'product_id'=>$item['product_id'],'packet_id'=>$item['packet_id']]);
                                if(!$p){
                                    $product = new EshopOrderPosition();
                                    $product->serialize($item);
                                    $this->_persist($product,'era');
                                }
                            }catch (Exception $e){
                                continue;
                            }
                        }
                    }
                    try{
                        $p = $this->repoEshoOrder->findOneBy(['order_id'=>$order['order_id'], 'order_status'=>$order['order_status'],'packet_id'=>$order['packet_id']]);
                        if(!$p){
                            $eShopOrder = new EshopOrder();
                            $eShopOrder->serialize($order);
                            $this->_persist($eShopOrder,'era');
                        }
                    }catch (Exception $e){
                        continue;
                    }
                }
            }
            $this->_flush();
        }
    }

    /**
     * @param $userId
     * @return array
     */
    public function iterateOrderItems($orders)
    {
        $out = [];
        if($orders){
            /** @var Order $order */
            foreach ($orders as $order){
                $basket = $this->repoBasket->findOneBy(['orderId' => $order->getOrderId()]);
                $out[] =[
                    'order' => $order, //->iterateVisible(),
                    'basket' => $basket, //->iterateVisible(),
                    'items' => $this->repoItem->findBy(['basketId' => $basket->getId()]),
                ];
            }
        }

        return $out;
    }

    /**
     * @param Order $order
     * @param $result
     * @param $out
     * @return array
     */
    protected function makeOutError(Order $order, $result, $out) : array {
        $out['result'] = $result;
        $out['message'] = isset($out['message']) ? $out['message'] : 'undefined error on line ' . __LINE__ . ' for  method' .  __METHOD__;
        $out['order_id'] = $order->getOrderId();
        $out['error'] = isset($out['error']) ? $out['error'] : null;
        $out['option'] = isset($out['option']) ? $out['option'] : null;
        return $out;
    }

    /**
     * @param array|null $destinations
     * @return bool
     */
    protected function checkDestinationToRM(?array $destinations) {
        if ( $destinations == null ) {
            return true;
        } else {
            return in_array(ShopConst::DST_RM, $destinations);
        }
    }

    /**
     * @param array|null $destinations
     * @return bool
     */
    protected function checkDestinationToMP(?array $destinations) {
        if ( $destinations == null ) {
            return true;
        } else {
            return in_array(ShopConst::DST_MP, $destinations);
        }
    }

    /**
     * @param string $orderId
     * @param string $eshopOrderPacketId
     * @return bool
     */
    private function isIncomingDostavkaOnly(string $orderId, string $eshopOrderPacketId) {
        $items = $this->repoEshoOrderPosition->findEshopOrderPositions($orderId, $eshopOrderPacketId);
        $kol_all = $kol_dost = 0;
        foreach ($items as $item) {
            $kol_all++;
            $kol_dost = (preg_match('/доставка/iu', $item['product_name'] )) ? ++$kol_dost : $kol_dost;
        }

        return $kol_all == $kol_dost && $kol_dost == 1;
    }

    /**
     * @param Order $order
     * @param EshopOrder $eshopOrder
     * @param int $result
     * @param string $message
     */
    private function consoleErrorToLog(Order $order, EshopOrder $eshopOrder, int $result, string $message) {
        $out = [
            'order_id' => $order->getOrderId(),
            'result'   => $result,
            'totime'   => DateTimeHelper::getInstance()->getDateString((new \DateTime()), 'H:i:s'),
            'message'  => $message,
            'status'   => $order->getStatus(),
        ];
        $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
        $out['eshop_order_id'] = $eshopOrder->getId();
        $out['eshop_status'] = trim($eshopOrder->getOrderStatus());
        $this->logService->create(__METHOD__, 'received_gw;' . AppHelper::jsonFromArray($out));
    }

    /**
     * @param int $basketId
     * @param Communicator $communicator
     * @param int|null $receiptOnlineId
     * @return array
     * @throws Exception
     */
    public function updateOrderStatusFromReceiptOnline(
        int $basketId,
        Communicator $communicator,
        int $receiptOnlineId = null,
        string $identifier = null
    ): array
    {
        $out = [];
        $basket = $this->repoBasket->findOneBy(['id' => $basketId]);
        $order  = $this->repoOrder->findOneBy(['orderId' => $basket->getOrderId()]);
        if($order->getStatus() == ShopConst::STATUS_PCRE) {
            $this->user = new User('console', null, ['ROLE_API']);
            $this->nameLogFile = 'info_order';
            $order->setConfirm(true);
            if($receiptOnlineId) {
                $status = ShopConst::STATUS_CRE;
                if ( empty($basket->getSoftCheque()) ) { // IT-701
                    $basket->setSoftCheque($identifier);
                }
            } else {
                $status = ShopConst::STATUS_RFW;
            }
            $params = ['status' => $status];
            $out = $this->setOrderStatus($order, $basket, $params, $communicator);
        }

        return $out;
    }

    /**
     * @param string $dateSubInterval
     * @return array
     */
    public function getOrdersSendsErrors(string $dateSubInterval): array
    {
        $orders = [];
        if( $sends = $this->messageController->getSends($dateSubInterval) ) {
            foreach ($sends as $send) {
                if( $historyOrderId = $send->getEvent()->getHistoryOrderId() ) {
                    if( $orderId = $this->getOrderIdByHistoryId($historyOrderId) ) {
                        if( !in_array($orderId, $orders) ) {
                            $orders[] = $orderId;
                        }
                    }
                }
            }
        }

        return [
            'result'  => Response::HTTP_OK,
            'message' => 'get orders with sends errors',
            'orders'  => $orders,
        ];
    }

    /**
     * @param int $historyOrderId
     * @return string|null
     */
    private function getOrderIdByHistoryId(int $historyOrderId): ?string
    {
        if( $orderHistory = $this->repoOrderHistory->findOneBy(['id' => $historyOrderId]) ) {

            return $orderHistory->getOrderId();
        }

        return null;
    }

}