<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 26.07.19
 * Time: 15:12
 */

namespace App\BasketOrderBundle\Service;

use App\BasketOrderBundle\Entity\Basket;
use App\BasketOrderBundle\Entity\Order;
use App\BasketOrderBundle\Helper\ItemHelper;
use App\BasketOrderBundle\Helper\ShopConst;
use App\CashboxBundle\Service\Cashbox\CashboxService;
use App\CashboxBundle\Service\MailerError\MailerErrorService;
use JMS\Serializer\SerializationContext;
use Metaer\CurlWrapperBundle\CurlWrapper;
use Psr\Log\LoggerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BasketService extends BaseService
{
    /**
     * BasketService constructor.
     * @param CashboxService $cashbox
     * @param DelayService $delayService
     * @param CurlWrapper $curlWrapper
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
     * @param $anonimId
     * @return Basket
     */
    public function createBasket($anonimId)
    {
        $basket = new Basket();
        $basket->setAnonimId($anonimId);
        $this->saveBasketActive($basket);
        $this->fPersist($basket);

        return $basket;
    }

    /**
     * @param Basket $basket
     */
    private function saveBasketActive(Basket $basket)
    {
        $anonimId = $basket->getAnonimId();
        $baskets = $this->repoBasket->findBy(['anonimId' => $anonimId, 'active' => true]);
        if ($baskets) {
            /** @var Basket $item */
            foreach ($baskets as $item) {
                $item->setActive(false);
                $this->em->persist($item);
            }
        }
        $basket->setActive(true);
    }

    /**
     * @param array $options
     * @param array $order
     * @return Basket[]|array|\object[]
     */
    public function findBy($options = [], $order = [])
    {
        $items = [];
        if ($options) {
            $items = $this->repoBasket->findBy($options, $order);
        }

        return $items;
    }

    /**
     * @param array $options
     * @return Basket|null|object
     */
    public function findOneBy($options = [])
    {
        $item = null;
        if ($options) {
            $item = $this->repoBasket->findOneBy($options);
        }

        return $item;
    }

    /**
     * @return Basket[]|\object[]
     */
    public function findAll()
    {
        $items = $this->repoBasket->findAll();

        return $items;
    }

    /**
     * @param $page
     * @param $limit
     * @param null $status
     * @return Basket[]|array|\object[]
     */
    public function allList($page, $limit, $status = null)
    {
        $offset = ($page - 1) * $limit;
        if ($status !== null and in_array($status, [true, false, '0', '1'])) {
            $baskets = $this->repoBasket->findBy(['active' => $status], ['created' => 'DESC', 'active' => 'DESC'], $limit, $offset);
        } else {
            $baskets = $this->repoBasket->findBy([], ['created' => 'DESC', 'active' => 'DESC'], $limit, $offset);

        }
        /** @var Basket $basket */
        foreach ($baskets as $basket) {
            $order = $this->repoOrder->findOneBy(['orderId' => $basket->getOrderId()]);
            $items = $this->repoItem->findBy(['basketId' => $basket->getId()]);
            $basket->setItemsCount(count($items));
            $basket->setOrderObject($order);
            $basket->setItemArray($items);
        }

        return $baskets;
    }

    /**
     * @param Basket $basket
     */
    private function clearBasket(Basket $basket)
    {
        $basket->clearBasket();
        $basket->setIdentifier(ShopConst::genHash([], []));
        $this->_persist($basket);
    }

    /**
     * @param $anonimId
     * @param null $storeId
     * @param null $basketId
     * @return array
     */
    public function createInfoForBasket($anonimId, $storeId=null,$basketId=null)
    {
        $basket = ($basketId !== null) ?
            $this->repoBasket->findOneBy(['anonimId' => $anonimId, 'id' => $basketId]) :
            $this->repoBasket->findOneBy(['anonimId' => $anonimId, 'active' => true]);
        if($basket) {
            $items = $this->repoItem->findBy(['basketId' => $basket->getId()]);
            $order = $basket->getOrderId() ? $this->repoOrder->findBy(['orderId' => $basket->getOrderId()]) : null;
            $out = [
                'result' => Response::HTTP_OK,
                'message' => 'info active basket',
                'store_id' => $storeId,
                'basket' => $basket,
                'order' => $order,
                'items' => $items,
                'coupons' => $this->getCouponsAppliedResult($basket, $items),
            ];

            return $out;
        }
        try {
            $basket = $this->createBasket($anonimId);
            $message = 'create new basket';
            $result = Response::HTTP_OK;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
            $result = Response::HTTP_INTERNAL_SERVER_ERROR;
            $message = 'error for create new basket';
        }
        $out = [
            'result' => $result,
            'message' => $message,
            'store_id' => $storeId,
        ];
        isset($basket) ? $out['basket'] = $basket : null;
        isset($order) ? $out['order'] = $order : null;
        isset($items) ? $out['items'] = $items : null;
        isset($error) ? $out['error'] = $error : null;
        isset($code) ? $out['code'] = $code : null;

        return $out;
    }

    /**
     * @param $anonimId
     * @param null $storeId
     * @param Basket|null $basket
     * @return array
     */
    public function createInfoForBasketV2($anonimId, $storeId = null, Basket $basket = null)
    {
        if($basket) {
            $result = Response::HTTP_OK;
            $message = 'info active basket';
            $items = $this->repoItem->findBy(['basketId' => $basket->getId()]);
            $order = $basket->getOrderId() ? $this->repoOrder->findBy(['orderId' => $basket->getOrderId()]) : null;
        } else {
            $items = null;
            try {
                $result = Response::HTTP_OK;
                $message = 'create new basket';
                $basket = $this->createBasket($anonimId);
            } catch (\Exception $e) {
                $result = Response::HTTP_INTERNAL_SERVER_ERROR;
                $message = 'error for create new basket';
                $basket = null;
            }
        }



        return [
            'result' => $result,
            'message' => $message,
            'store_id' => $storeId,
            'basket' => $this->serializer->serialize($basket, 'json', SerializationContext::create()->setGroups(array('Default'))),
            'order' => $this->serializer->toArray($order),
            'items' => $this->serializer->toArray($items),
            'coupons' => $this->getCouponsAppliedResult($basket, $items),
        ];
    }

    /**
     * @param $anonimId
     * @param $userId
     * @return array
     */
    public function getListBaskets($anonimId, $userId)
    {
        $baskets = $this->findBy(['anonimId' => $anonimId], ['active' => 'DESC']);
        if ($userId) {
            if ($baskets) {
                /** @var Basket $basket */
                foreach ($baskets as $basket) {
                    /** @var Order $order */
                    $order = $this->repoOrder->findOneBy(['userId' => $userId]);
                    if ($order) {
                        $bskt = $this->repoBasket->findOneBy(['orderId' => $order->getOrderId()]);
                        if ($bskt) {
                            $baskets[] = $bskt;
                        }
                    }
                }
            }
        }
        if (empty($baskets)) {
            $basket = $this->createBasket($anonimId);
            $baskets[] = $basket;
        }
        $out = [];
        /** @var Basket $basket */
        foreach ($baskets as $basket) {
            $basket->setItemsCount(count($this->repoItem->findBy(['basketId' => $basket->getId()])));
            $out[] = $basket; //->iterateVisible();
        }
        $out = [
            'result' => Response::HTTP_OK,
            'message' => 'list baskets for anonim user',
            'baskets' => $out,
        ];

        return $out;
    }

    /**
     * @param Basket $basket
     * @param $itemData
     * @param bool $sendCashBox
     * @return array
     */
    public function addItemsToBasket(Basket $basket, $itemData, $sendCashBox = true, $issetDiscounts = false)
    {
        $items = $this->addItems($basket, $itemData, $issetDiscounts);
        if($sendCashBox){
            $out = $this->sendCashBox($basket, $items);
            $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
            if ($result != Response::HTTP_OK) {

                return $out;
            }
        } else {
            $result = Response::HTTP_OK;
        }
        $this->updateOrderForCheckout($basket);
        $this->_persist($basket);
        $this->_flush();
        $out = [
            'result' => $result,
            'message' => 'articles added into basket',
            'store_id' => $basket->getStoreId(),
            'basket' => $basket, //->iterateVisible(),
            'items' => $this->repoItem->findBy(['basketId' => $basket->getId()]),
        ];
        return $out;
    }

    /**
     * @param Basket $basket
     * @param $article
     * @param $itemQty
     * @return array
     */
    public function updateCounterItemForBasket(Basket $basket, $article, $itemQty)
    {
        $items = $this->updateCounters($basket, $article, $itemQty);
        $out = $this->sendCashBox($basket, $items);
        $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
        if ($result != Response::HTTP_OK) {

            return $out;
        }
        $this->updateOrderForCheckout($basket);
        $this->_flush();
        $out = [
            'result' => $result,
            'message' => 'Qty for item updated',
            'store_id' => $basket->getStoreId(),
            'basket' => $basket, //->iterateVisible(),
            'items' => $this->repoItem->findBy(['basketId' => $basket->getId()]),
        ];

        return $out;
    }

    /**
     * @param Basket $basket
     * @return array
     */
    private function getAllCouponsCurrent(Basket $basket): array
    {
        $couponsCurrent = $basket->getCoupons() ?: [];
        $couponUserCurrent = $basket->prepareCouponNumber($basket->getCouponUser());

        return [$couponsCurrent, $couponUserCurrent];
    }

    /**
     * @param Basket $basket
     * @param array|null $coupons
     * @return array
     */
    public function addCouponsToBasket(Basket $basket, ?array $coupons): array
    {
        list($couponsCurrent, $couponUserCurrent) = $this->getAllCouponsCurrent($basket);
        if($coupons) {
            foreach ($coupons as $coupon) {
                $couponNumber = $basket->prepareCouponNumber($coupon['number']);
                if( !array_key_exists($couponNumber, $couponsCurrent) ) {
                    $couponsCurrent[$couponNumber] = [
                        'number' => $couponNumber,
                    ];
                }
            }
            $basket->setCoupons($couponsCurrent);
        }

        return $this->setCouponToBasketProcess($basket);
    }

    /**
     * @param Basket $basket
     * @param array|null $coupons
     * @return array
     */
    public function delCouponsFromBasket(Basket $basket, ?array $coupons): array
    {
        list($couponsCurrent, $couponUserCurrent) = $this->getAllCouponsCurrent($basket);
        $coupons = empty($coupons) ? $couponsCurrent : $coupons;
        if($couponsCurrent) {
            foreach ($coupons as $coupon) {
                $couponNumber = $basket->prepareCouponNumber($coupon['number']);
                if(!$couponUserCurrent || ($couponNumber != $couponUserCurrent) ) {
                    if (array_key_exists($couponNumber, $couponsCurrent)) {
                        unset($couponsCurrent[$couponNumber]);
                    }
                }
            }
            $basket->setCoupons($couponsCurrent);
        }

        return $this->setCouponToBasketProcess($basket);
    }

    /**
     * @param Basket $basket
     * @param $couponUser
     * @return array
     */
    public function addCouponUserToBasket(Basket $basket, $couponUser): array
    {
        list($couponsCurrent, $couponUserCurrent) = $this->getAllCouponsCurrent($basket);
        $couponUser = $basket->prepareCouponNumber($couponUser);
        foreach ($couponsCurrent as $coupon) {
            $couponNumber = $basket->prepareCouponNumber($coupon['number']);
            if ($couponUserCurrent && $couponUserCurrent == $couponNumber) {
                unset($couponsCurrent[$couponNumber]);
                $basket->setCouponUser(null);
                break;
            }
        }
        if ($couponUser) {
            $basket->setCouponUser($couponUser);
            $couponsCurrent[$couponUser] = [
                'number' => $couponUser,
            ];
        }
        $basket->setCoupons($couponsCurrent);

        return $this->setCouponToBasketProcess($basket);
    }

    /**
     * @param Basket $basket
     * @return array
     */
    public function delCouponUserFromBasket(Basket $basket): array
    {
        list($couponsCurrent, $couponUserCurrent) = $this->getAllCouponsCurrent($basket);
        foreach ($couponsCurrent as $coupon) {
            $couponNumber = $basket->prepareCouponNumber($coupon['number']);
            if ($couponUserCurrent && $couponUserCurrent == $couponNumber) {
                unset($couponsCurrent[$couponNumber]);
                break;
            }
        }
        $basket->setCouponUser(null);
        $basket->setCoupons($couponsCurrent);

        return $this->setCouponToBasketProcess($basket);
    }

    /**
     * @param Basket $basket
     * @return array
     */
    private function setCouponToBasketProcess(Basket $basket): array
    {
        $items = $this->repoItem->findBy(['basketId' => $basket->getId(),]);
        $order = $basket->getOrderId() ? $this->repoOrder->findOneBy(['orderId' => $basket->getOrderId()]) : null;
        $actions = $this->collectBasketActions($basket);
        $basket->setActions($actions);
        if($order) {
            $order->setActions($actions);
            if($items) {
                $out = $this->sendCashBox($basket, $items);
                $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
                if ($result != Response::HTTP_OK) {

                    return $out;
                }
                $this->updateOrderForCheckout($basket, $order);
                $this->_flush();

                return [
                    'result' => $result,
                    'message' => 'success',
                    'store_id' => $basket->getStoreId(),
                    'basket' => $basket,
                    'order' => $order,
                    'items' => $items,
                    'coupons' => $this->getCouponsAppliedResult($basket, $items),
                ];
            } else {

                return [
                    'result' => Response::HTTP_NOT_FOUND,
                    'message' => 'items not found, but there is order',
                    'basket_id' => $basket->getId(),
                    'store_id' => $basket->getStoreId(),
                ];
            }
        } else {
            if(!$items) {
                $out = [
                    'result' => Response::HTTP_OK,
                ];
            } else {
                $out = $this->sendCashBox($basket, $items);
            }
            $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
            if ($result != Response::HTTP_OK) {

                return $out;
            }
            $this->updateOrderForCheckout($basket);
            $this->_flush();

            return [
                'result' => $result,
                'message' => 'success',
                'store_id' => $basket->getStoreId(),
                'basket' => $basket,
                'items' => $items,
                'coupons' => $this->getCouponsAppliedResult($basket, $items),
            ];
        }
    }

    /**
     * @param Basket $basket
     * @param $card
     * @return array
     */
    public function setCardToBasket(Basket $basket, $card)
    {
        $basket->setClearCard($card);
        $orderId = $basket->getOrderId();
        $items = $this->repoItem->findBy(['basketId' => $basket->getId()]);
        if($orderId) {
            $order = $this->repoOrder->findOneBy(['orderId' => $orderId]);
            if($items) {
                $out = $this->sendCashBox($basket, $items, null, true);
                $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
                if ($result != Response::HTTP_OK) {

                    return $out;
                }
                $this->updateOrderForCheckout($basket, $order);
                $this->_flush();
                $out = [
                    'result' => $result,
                    'message' => 'card set into basket',
                    'store_id' => $basket->getStoreId(),
                    'basket' => $basket,
                    'order' => $order,
                    'items' => $items,
                ];

                return $out;
            } else {
                $this->updateOrderForCheckout($basket, $order);
                $this->_flush();
                $out = [
                    'result' => Response::HTTP_NOT_FOUND,
                    'message' => 'items not found, but card set into basket',
                    'basket_id' => $basket->getId(),
                    'store_id' => $basket->getStoreId(),
                    'basket' => $basket,
                    'order' => $order,
                ];

                return $out;
            }
        } else {
            if(!$items) {
                $out = [
                    'result' => Response::HTTP_OK,
                ];
            } else {
                $out = $this->sendCashBox($basket, $items, null, true);
            }
            $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
            if ($result != Response::HTTP_OK) {

                return $out;
            }
            $this->updateOrderForCheckout($basket);
            $this->_flush();
            $out = [
                'result' => $result,
                'message' => 'card set into basket',
                'store_id' => $basket->getStoreId(),
                'basket' => $basket,
                'items' => $items,
            ];

            return $out;
        }
    }

    /**
     * @param Basket $basket
     * @param $userId
     * @param $card
     * @param array $partnerData
     * @param bool $sendCashBox
     * @param bool $isUseCasheBox
     * @return array
     */
    public function checkoutToBasket(Basket $basket, $userId, $card, $partnerData = [], $sendCashBox = true, $isUseCasheBox = true, $orderSourceIdentifier = null)
    {
        $items = $this->repoItem->findBy(['basketId' => $basket->getId(),]);
        if (!$items) {
            $out = [
                'result' => Response::HTTP_NOT_FOUND,
                'message' => 'items not found',
                'basket_id' => $basket->getId(),
                'store_id' => $basket->getStoreId(),
            ];

            return $out;
        }
        if(strlen($card)>0 && !$basket->checkCard($card)) {
            $out = [
                'result' => Response::HTTP_BAD_REQUEST,
                'message' => 'wrong card num',
                'basket_id' => $basket->getId(),
                'store_id' => $basket->getStoreId(),
            ];

            return $out;
        }
        $basket->setClearCard($card);
        !empty($partnerData['card_num_partner']) ? $basket->setCardNumPartner($partnerData['card_num_partner']) : null;
        if($sendCashBox) {
            $out = $this->sendCashBox($basket, $items);
            $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
            if ($result != Response::HTTP_OK) {

                return $out;
            }
        }
        $result = Response::HTTP_OK;
        $number = $basket->getOrderId();
        $order = $number ? $this->repoOrder->findOneBy(['orderId' => $number]) : null;
        $message = 'order created';
        if (!$order) {
            $message = 'order create';
            $order = $this->createOrder();
            $basket->setOrderId($order->getOrderId());
        }
//        $actions = $this->collectBasketActions($basket);
//        $order->setActions($actions);
        !empty($partnerData['order_id_partner']) ? $order->setOrderIdPartner($partnerData['order_id_partner']) : null;
        !empty($partnerData['delivery_cost_sum_partner']) ? $order->setDeliveryCostSumPartner($partnerData['delivery_cost_sum_partner']) : null;
        $historyOrderId = $this->insertIntoOrderHistory($order, $basket);
        $orderSourceIdentifier ? $order->setSourceIdentifier($orderSourceIdentifier) : null;
        $this->updateOrderForCheckout($basket, $order, $userId);
        /* Пока временно отключаем. Возможно, отключили на постоянку.
                if(!$isUseCasheBox) {
                    $cashboxResponse = $this->sendCashBoxReadOnly($basket, $items, $order);
                    $costToDiff = [
                        'costOrder' => $order->getCost(),
                        'costCashBox' => $cashboxResponse['cost'] ?? 0,
                    ];
                    if( $this->tooMuchCostDiff($costToDiff) ) {
                        $order->setStatus(ShopConst::STATUS_BLC);
                        $this->insertIntoOrderHistory($order, $basket);
                        $reason = 'Order block';
                        $data = [
                            'reason' => $reason,
                            'orderId' => $order->getOrderId(),
                            'orderDataIn' => print_r(ItemHelper::itemsArray($items), 1),
                            'orderDataCashbox' => print_r($cashboxResponse['items'], 1),
                            'costOrder' => $order->getCost(),
                            'costCashBox' => $cashboxResponse['cost'] ?? 0,
                        ];
                        $this->createBasketOrderError($reason, $data, $this->container->get('dc.mailer'));
                    }
                }
        */
        $this->_flush();
        $out = [
            'result' => $result,
            'message' => $message,
            'store_id' => $basket->getStoreId(),
            'basket' => $basket, //->iterateVisible(),
            'order' => $order, //->iterateVisible(),
            'items' => $this->repoItem->findBy(['basketId' => $basket->getId()]),
        ];

        return $out;
    }

    /**
     * @param string $userId
     * @return int
     */
    public function countOfOrders(string $userId): int
    {
        return $this->repoOrder->findNotProcessedOrdersCount($userId);
    }

    /**
     * @param string $reason
     * @param array $template_data
     * @param MailerErrorService $mailer
     */
    public function createBasketOrderError(string $reason, array $template_data, MailerErrorService $mailer)
    {
        $mailer->send($reason, $template_data);
    }

    /**
     * @param Basket $basket
     * @param $article
     * @return array
     */
    public function removeFromBasket(Basket $basket, $article)
    {
        $items = $this->repoItem->findBy(['basketId' => $basket->getId(),]);
        $items = $this->removeItem($basket, $article, $items);

        if($items){
            $out = $this->sendCashBox($basket, $items);
        }else{
            $out = [
                'result' => Response::HTTP_OK,
                'message' => 'items have been removed before',
            ];
        }
        $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
        if ($result != Response::HTTP_OK) {

            return $out;
        }
        $this->updateOrderForCheckout($basket);
        $this->_flush();
        $out = [
            'result' => $result,
            'message' => 'item '.$article.' from basket removed',
            'store_id' => $basket->getStoreId(),
            'basket' => $basket, //->iterateVisible(),
            'items' => $this->repoItem->findBy(['basketId' => $basket->getId()]),
        ];

        return $out;
    }

    /**
     * @param Basket $basket
     * @return array
     */
    public function clearFromBasket(Basket $basket)
    {
        $items = $this->repoItem->findBy(['basketId' => $basket->getId()]);
        if ($items) {
            foreach ($items as $item) {
                $this->em->remove($item);
            }
        }
        $this->clearBasket($basket);
        $this->updateOrderForCheckout($basket);
        $this->_flush();
        $out = [
            'result' => Response::HTTP_OK,
            'message' => 'basket cleared',
            'store_id' => $basket->getStoreId(),
            'basket' => $basket, //->iterateVisible(),
        ];

        return $out;
    }

    /**
     * @param Basket $basket
     * @param $paymentType
     * @return array
     */
    public function isCouponForPaymentOnLine(Basket $basket, $paymentType)
    {
        $pTypes = ['0', '1'];
        if (!in_array($paymentType, $pTypes)) {
            $out = [
                'result' => Response::HTTP_BAD_REQUEST,
                'message' => 'payment type not aviable list types',
                'basket_id' => $basket->getId(),
                'store_id' => $basket->getStoreId(),
            ];

            return $out;
        }
        if ($paymentType == ShopConst::PAYMENT_KEY_TYPE_O) {
//            $basket->addCoupon($this->getCouponOnline()['number']);
        } elseif ($paymentType == ShopConst::PAYMENT_KEY_TYPE_C) {
            $coupons = $basket->getCoupons();
            $items = [];
            if ($coupons) {
                foreach ($coupons as $key => $coupon) {
                    if ($key != $this->getCouponOnline()['number']) {
                        $items[$key] = $coupon;
                    }
                }
                $basket->setCoupons($items);
            }
        }
        $items = $this->repoItem->findBy(['basketId' => $basket->getId(),]);
        if(!$items){
            $out = [
                'result' => Response::HTTP_OK,
            ];
        }else{
            $out = $this->sendCashBox($basket, $items);
        }

        $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
        if ($result != Response::HTTP_OK) {

            return $out;
        }
        $this->updateOrderForCheckout($basket);
        $this->_flush();
        $out = [
            'result' => $result,
            'message' => 'payment type set into basket',
            'store_id' => $basket->getStoreId(),
            'basket' => $basket, //->iterateVisible(),
            'items' => $this->repoItem->findBy(['basketId' => $basket->getId()]),
        ];

        return $out;
    }

    /**
     * @param Basket $basket
     * @return array
     */
    public function clearCardForBasket(Basket $basket)
    {
        $basket->setClearCard('clear');
        $orderId = $basket->getOrderId();
        $items = $this->repoItem->findBy(['basketId' => $basket->getId()]);
        if($orderId) {
            $order = $this->repoOrder->findOneBy(['orderId' => $orderId]);
            if(!$items) {
                $this->updateOrderForCheckout($basket, $order);
                $this->_flush();
                $out = [
                    'result' => Response::HTTP_NOT_FOUND,
                    'message' => 'items not found, but card is cleared',
                    'basket_id' => $basket->getId(),
                    'store_id' => $basket->getStoreId(),
                    'basket' => $basket,
                    'order' => $order,
                ];

                return $out;
            }
            $out = $this->sendCashBox($basket, $items, null, true);
            $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
            if ($result != Response::HTTP_OK) {

                return $out;
            }
            $this->updateOrderForCheckout($basket, $order);
            $this->_flush();
            $out = [
                'result' => $result,
                'message' => 'card set into basket',
                'store_id' => $basket->getStoreId(),
                'basket' => $basket,
                'order' => $order,
                'items' => $items,
            ];

            return $out;
        } else {
            if(!$items) {
                $out = [
                    'result' => Response::HTTP_OK,
                ];
            } else {
                $out = $this->sendCashBox($basket, $items);
            }
            $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
            if ($result != Response::HTTP_OK) {

                return $out;
            }
            $this->updateOrderForCheckout($basket);
            $this->_flush();
            $out = [
                'result' => $result,
                'message' => 'card cleared into basket',
                'store_id' => $basket->getStoreId(),
                'basket' => $basket,
                'items' => $items,
            ];

            return $out;
        }
    }
}