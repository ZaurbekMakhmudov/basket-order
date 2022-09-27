<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 12.07.19
 * Time: 19:11
 */

namespace App\BasketOrderBundle\Controller;

use App\BasketOrderBundle\Entity\Basket;
use App\BasketOrderBundle\Entity\CouponUser;
use App\BasketOrderBundle\Entity\Order;
use App\BasketOrderBundle\Helper\AppHelper;
use App\BasketOrderBundle\Helper\ShopConst;
use App\BasketOrderBundle\Notification\CouponNotification;
use App\BasketOrderBundle\Service\BasketService;
use App\BasketOrderBundle\Service\DeliveryClubService;
use App\BasketOrderBundle\Service\ItemService;
use App\BasketOrderBundle\Service\LogService;
use App\BasketOrderBundle\Service\OrderService;
use App\BasketOrderBundle\Service\DelayService;
use App\BasketOrderBundle\Service\SberMarketService;
use App\BasketOrderBundle\Service\SMMService;
use App\BasketOrderBundle\SwgModel\Coupon;
use App\BasketOrderBundle\SwgModel\OrderComplex;
use App\BasketOrderBundle\SwgModel\Overtime;
use App\BasketOrderBundle\SwgModel\PaymentInformation;
use App\BasketOrderBundle\SwgModel\DeliveryClub\City;
use App\BasketOrderBundle\SwgModel\DeliveryClub\Coordinates;
use App\BasketOrderBundle\SwgModel\DeliveryClub\CreateOrder;
use App\BasketOrderBundle\SwgModel\DeliveryClub\Customer;
use App\BasketOrderBundle\SwgModel\DeliveryClub\Delivery;
use App\BasketOrderBundle\SwgModel\DeliveryClub\Payment;
use App\BasketOrderBundle\SwgModel\DeliveryClub\Position;
use App\BasketOrderBundle\SwgModel\DeliveryClub\Status;
use App\BasketOrderBundle\SwgModel\DeliveryClub\Total;
use App\SemaphoreBundle\SemaphoreKeyStorage;
use App\SemaphoreBundle\SemaphoreLocker;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use WebPlatform\InGatewayBundle\Communicator\Communicator;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class BaseController extends FOSRestController
{
    /** @var array */
    protected $errors = [];
    /** @var OrderService */
    protected $orderService;
    /** @var BasketService */
    protected $basketService;
    /** @var ItemService */
    protected $itemService;
    /** @var DelayService */
    protected $delayService;
    /** @var  Communicator */
    private $communicator;
    /** @var  string */
    protected $logfileName;
    /** @var string */
    protected $env;
    /**
     * @var DeliveryClubService
     */
    protected DeliveryClubService $deliveryClubService;

    /**
     * @var SMMService
     */
    protected SMMService $SMMService;

    /**
     * @var LogService
     */
    protected LogService $logService;

    /**
     * @var ValidatorInterface
     */
    protected ValidatorInterface $validator;


    protected Security $security;

    protected SemaphoreLocker $semaphoreLocker;

    protected SemaphoreKeyStorage $semaphoreKeyStage;
    protected EntityManager $entityManager;
    protected SberMarketService $sberMarketService;
    /**
     * @var LoggerInterface
     */


    /**
     * BaseController constructor.
     * @param BasketService $basketService
     * @param ItemService $itemService
     * @param LogService $logService
     * @param OrderService $orderService
     * @param DelayService $delayService
     * @param DeliveryClubService $deliveryClubService
     * @param ValidatorInterface $validator
     * @param Security $security
     */
    function __construct(
        BasketService $basketService,
        ItemService $itemService,
        OrderService $orderService,
        DelayService $delayService,
        DeliveryClubService $deliveryClubService,
        SMMService $SMMService,
        SberMarketService $sberMarketService,
        ValidatorInterface $validator,
        Security $security,
        EntityManagerInterface $entityManager
    )
    {
        $this->basketService = $basketService;
        $this->itemService = $itemService;
        $this->orderService = $orderService;
        $this->delayService = $delayService;
        $this->deliveryClubService = $deliveryClubService;
        $this->SMMService = $SMMService;
        $this->sberMarketService = $sberMarketService;
        $this->validator = $validator;
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function setVars(
        $logService
    )
    {
        $this->logService = $logService;
    }

    public function setSemaphore($semaphoreLocker, $semaphoreKeyStage)
    {
        $this->semaphoreLocker = $semaphoreLocker;
        $this->semaphoreKeyStage = $semaphoreKeyStage;
    }

    /**
     * @param $logfileName
     */
    public function setLogfileName($logfileName)
    {
        $this->logfileName = $logfileName;
        $this->basketService->setNameLogFile($logfileName);
        $this->orderService->setNameLogFile($logfileName);
    }

    /**
     * @return mixed
     */
    public function getLogfileName()
    {
        return $this->logfileName;

    }

    /**
     * @param Request|null $request
     * @return mixed|null
     */
    public function getStoreId(Request $request=null)
    {
        $storeId = $request ? $request->get('store_id') : null;
        if (!$storeId) {
            $storeId = $_ENV['cashbox_shop'];
        }

        return $storeId;
    }

    /**
     * @param Request $request
     * @param Basket $basket
     * @return Basket
     */
    public function setBasketStoreId(Basket $basket, Request $request=null)
    {
        $storeId = $basket->getStoreId();
        if (!$storeId) {
            $storeId = $this->getStoreId($request);
            $basket->setStoreId($storeId);
        }

        return $basket;
    }

    /**
     * @param Order $order
     * @param Request|null $request
     * @return Basket|null|object
     */
    public function getBasketStoreId(Order $order, Request $request=null)
    {
        $basket = $this->basketService->findOneBy(['orderId' => $order->getOrderId()]);
        $storeId = $basket->getStoreId();
        if (!$storeId) {
            $storeId = $this->getStoreId($request);
            $basket->setStoreId($storeId);
        }

        return $basket;
    }

    /**
     * @param Communicator $communicator
     */
    public function setInGatewayCommunicator(Communicator $communicator)
    {
        $this->communicator = $communicator;
    }

    /**
     * @return Communicator
     */
    public function getInGatewayCommunicator()
    {
        return $this->communicator;
    }

    /**
     * @param Request $request
     * @return Basket|object|Response|null
     */
    protected function noValidateInfo(Request $request)
    {
        $anonimId = $request->get('anonim_id');
        if (!$anonimId) {

            return $this->makeBadReqResp('anonim ID required');
        }
        $cond['anonimId'] = $anonimId;
        $basketId = $request->get('basket_id');
        if ($basketId !== null) {
            $cond['id'] = $basketId;
        } else {
            $cond['active'] = true;
        }

        return $this->basketService->findOneBy($cond);
    }

    /**
     * @param Request $request
     * @return false|Response
     */
    protected function noValidateOrder(Request $request)
    {
        if (!$request->get('anonim_id')) {

            return $this->makeBadReqResp('anonim ID required');
        }
        if (!$request->get('user_id')) {

            return $this->makeBadReqResp('user ID required');
        }
        $requestBody = json_decode($request->getContent(), true);
        $orderComplex = new orderComplex();
        $orderComplex->payment_type = $requestBody['payment_type'] ?? null;
        $orderComplex->delivery_type = $requestBody['delivery_type'] ?? null;
        $orderComplex->customer = $requestBody['customer'] ?? null;
        $orderComplex->delivery = $requestBody['delivery'] ?? null;
        $orderComplex->items = $requestBody['items'] ?? null;
        $orderComplex->card = $requestBody['card'] ?? null;
        $isValid = $this->isValidObject($orderComplex);
        if(!$isValid['isValid']) {

            return $this->makeBadReqResp($isValid['errors']);
        }

        return false;
    }

    /**
     * @param Request $request
     * @return bool|Response
     */
    protected function noValidateList(Request $request)
    {
        $anonimId = $request->get('anonim_id');
        if (!$anonimId) {

            return $this->makeBadReqResp('anonim ID required');
        }

        $userId = $request->get('user_id');
        if (!$userId) {

            return $this->makeBadReqResp('user ID required');
        }

        return false;
    }

    /**
     * @param Request $request
     * @param $basketId
     * @return Basket|Response
     */
    protected function noValidateAdd(Request $request, $basketId)
    {
        if (!$basketId) {

            return $this->makeBadReqResp('basket ID required');
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $basket = $this->basketService->findOneBy(['id' => $basketId]);
        }else{
            $basket = $this->basketService->findOneBy(['id' => $basketId, 'active' => true]);
        }

        if (!$basket) {

            return $this->makeBasketNotFoundResp(__METHOD__, __LINE__, $basketId);
        }

        $requestBody = json_decode($request->getContent(), true);
        $itemData = ($requestBody and isset($requestBody['items'])) ? $requestBody['items'] : null;
        if ($this->basketService->isValidItemData($itemData)) {

            return $this->makeBasketBadReqResp(__METHOD__, __LINE__, $basketId);
        }

        return $basket;
    }

    /**
     * @param Request $request
     * @param $basketId
     * @return bool|Response
     */
    protected function noValidateUpdateCounters(Request $request, $basketId)
    {
        if (!$basketId) {

            return $this->makeBadReqResp('basket ID required');
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $basket = $this->basketService->findOneBy(['id' => $basketId]);
        }else{
            $basket = $this->basketService->findOneBy(['id' => $basketId, 'active' => true]);
        }

        if (!$basket) {

            return $this->makeBasketNotFoundResp(__METHOD__, __LINE__, $basketId);
        }

        $requestBody = json_decode($request->getContent(), true);
        $article = ($requestBody and isset($requestBody['article'])) ? $requestBody['article'] : null;
        if (!$article) {

            return $this->makeBasketBadReqResp(__METHOD__, __LINE__, $basketId, 'article not defined', $requestBody);
        }

        $item = $basket ? $this->itemService->findOneBy(['article' => $article, 'basketId' => $basket->getId()]) : null;
        if (!$item) {

            return $this->makeBasketNotFoundResp(__METHOD__, __LINE__, $basketId, "item <{$article}> not found", $requestBody);
        }

        $itemQty = ($requestBody and isset($requestBody['item_qty'])) ? $requestBody['item_qty'] : null;
        if ($itemQty === null || $itemQty < 0) {

            return $this->makeBasketBadReqResp(__METHOD__, __LINE__, $basketId, 'Qty not defined', $requestBody);
        }

        return false;
    }

    /**
     * @param Request $request
     * @param $basketId
     * @param bool $doDelete
     * @return array
     */
    protected function noValidateCoupon(Request $request, $basketId, bool $doDelete = false): array
    {
        if (!$basketId) {

            return [$this->makeBadReqResp('basket ID required'), null, null, null];
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $basket = $this->basketService->findOneBy(['id' => $basketId]);
        }else{
            $basket = $this->basketService->findOneBy(['id' => $basketId, 'active' => true]);
        }

        if (!$basket) {

            return [$this->makeBasketNotFoundResp(__METHOD__, __LINE__, $basketId), null, null, null];
        }

        $requestBody = json_decode($request->getContent(), true);
        if( empty($requestBody['coupons']) && empty($requestBody['coupon']) ) {
            if($doDelete) {

                return [$basket, null, null, null];
            } else {

                return [$this->makeBasketBadReqResp(__METHOD__, __LINE__, $basketId, 'coupons is required', $requestBody), null, null, null];
            }
        }

        if( $orderId = $basket->getOrderId() ) {
            $items = $this->itemService->findOneBy(['basketId' => $basket->getId()]);
            if (!$items) {

                return [$this->makeBasketNotFoundResp(__METHOD__, __LINE__, $basketId, 'items not found, but there is order', $requestBody), null, null, null];
            }
        }
        $couponsVerified = [];
        $couponUserVerified = null;
        if( !empty($requestBody['coupon']) ) {
            $coupon = $requestBody['coupon'];
            $coupon['number'] = $basket->prepareCouponNumber($coupon['number']);
            if( $couponVerified = $this->couponValidate($coupon, $orderId, $doDelete) ) {
                $couponsVerified[] = $couponVerified;
                $couponUserVerified = $couponVerified['number'];
            }
        } else {
            foreach ($requestBody['coupons'] as $coupon) {
                $coupon['number'] = $basket->prepareCouponNumber($coupon['number']);
                if( $couponVerified = $this->couponValidate($coupon, $orderId, $doDelete) ) {
                    $couponsVerified[] = $couponVerified;
                }
            }
        }
        $couponNotifications = count($this->basketService->couponNotificationCollection) == 0 ? null :
            $this->basketService->serializer->toArray($this->basketService->couponNotificationCollection);

        return [$basket, $couponsVerified, $couponUserVerified, $couponNotifications];
    }

    /**
     * @param array $couponData
     * @param string|null $orderId
     * @param bool $doDelete
     * @return array|false
     */
    private function couponValidate(array $couponData, ?string $orderId, bool $doDelete)
    {
        $level = $message = $reason = '';
        $coupon = new Coupon();
        $coupon->number = $couponData['number'];
        $coupon->type = $couponData['type'] ?? null;
        $isValid = $this->isValidObject($coupon);
        $message = 'Купон не применен к данной корзине';
        if ( !$isValid['isValid'] ) {
            $code = Response::HTTP_BAD_REQUEST;
            $level = CouponNotification::LEVEL_ERROR;
            $reason = 'Некорректный купон';
        } else {
            $code = Response::HTTP_OK;
            if(!$doDelete) {
                $restrictionCountOrders = 0;
                $restrictionOnce = $restrictionFreeDelivery = $couponDisabled = false;
                foreach ($this->basketService->getCouponRestrictionTypes($coupon->number) as $couponRestrictionType) {
                    if($couponRestrictionType == ShopConst::COUPON_TYPE_DISABLED) {
                        $couponDisabled = true;
                        break;
                    }
                    if($couponRestrictionType == ShopConst::COUPON_TYPE_ONCE) {
                        $restrictionOnce = true;
                    }
                    if($couponRestrictionType == ShopConst::COUPON_TYPE_DELIVERY_FREE) {
                        $restrictionFreeDelivery = true;
                    }
                    if($couponRestrictionType == ShopConst::COUPON_TYPE_1_ORDER) {
                        $restrictionCountOrders = 1;
                    }
                    if($couponRestrictionType == ShopConst::COUPON_TYPE_2_ORDER) {
                        $restrictionCountOrders = 2;
                    }
                    if($couponRestrictionType == ShopConst::COUPON_TYPE_3_ORDER) {
                        $restrictionCountOrders = 3;
                    }
                }
                if($couponDisabled) {
                    list($code, $level, $reason) = $this->couponDisabled();
                }
                if($restrictionCountOrders > 0) {
                    list($code, $level, $reason) = $this->checkRestrictionsCountOrder($restrictionCountOrders, $orderId);
                }
                if($restrictionOnce) {
                    list($code, $level, $reason) = $this->checkRestrictionsOnce($orderId, $coupon->number);
                }
                if($restrictionFreeDelivery) {
                    list($code, $level, $reason) = $this->checkRestrictionFreeDelivery($orderId);
                }
            }
        }

        if($code != Response::HTTP_OK) {
            $this->addCouponNotification($code, $level, $message, $reason, $coupon->number);
            
            return false;
        } else {
            
            return $couponData;
        }
    }

    /**
     * @return array
     */
    private function couponDisabled(): array
    {
        $code = Response::HTTP_NOT_FOUND;
        $level = CouponNotification::LEVEL_WARNING;
        $reason = 'Данный купон более недействителен';

        return [$code, $level, $reason];
    }

    /**
     * @param int $restrictionCountOrders
     * @param string|null $orderId
     * @return array
     */
    private function checkRestrictionsCountOrder(int $restrictionCountOrders, ?string $orderId): array
    {
        $code = Response::HTTP_OK;
        $level = $reason = null;
        $order = $orderId ? $this->basketService->repoOrder->findOneBy(['orderId' => $orderId]) : null;
        if (!$order) {
            $code = Response::HTTP_NOT_FOUND;
            $level = CouponNotification::LEVEL_WARNING;
            $reason = 'У текущего пользователя не найден ни один заказ';
        } else {
            $currentCountOfOrders = $this->basketService->countOfOrders($order->getUserId());
            if ($currentCountOfOrders >= $restrictionCountOrders) {
                $code = Response::HTTP_NOT_FOUND;
                $level = CouponNotification::LEVEL_NOTICE;
                $reason = "У текущего пользователя больше {$restrictionCountOrders} заказов";
            }
        }

        return [$code, $level, $reason];
    }

    /**
     * @param string|null $orderId
     * @param string $couponNumber
     * @return array
     */
    private function checkRestrictionsOnce(?string $orderId, string $couponNumber): array
    {
        $code = Response::HTTP_OK;
        $level = $reason = null;
        $order = $orderId ? $this->basketService->repoOrder->findOneBy(['orderId' => $orderId]) : null;
        if (!$order) {
            $code = Response::HTTP_NOT_FOUND;
            $level = CouponNotification::LEVEL_WARNING;
            $reason = 'У текущего пользователя не найден ни один заказ';
        } else {
            $userId = $order->getUserId();
            $countCouponUser = $this->basketService->em->getRepository(CouponUser::class)->getCountCouponUser($userId, $couponNumber);
            if($countCouponUser > 0) {
                $code = Response::HTTP_BAD_REQUEST;
                $level = CouponNotification::LEVEL_NOTICE;
                $reason = 'Текущий пользователь уже использовал этот купон';
            } else {
                $couponUser = new CouponUser();
                $couponUser->setUserId($userId);
                $couponUser->setCouponNumber($couponNumber);
                $couponUser->setInsertedAt(new DateTime());
                $this->basketService->em->persist($couponUser);
            }
        }

        return [$code, $level, $reason];
    }

    /**
     * @param string|null $orderId
     * @return array
     */
    private function checkRestrictionFreeDelivery(?string $orderId): array
    {
        $code = Response::HTTP_OK;
        $level = $reason = null;
        $order = $orderId ? $this->basketService->repoOrder->findOneBy(['orderId' => $orderId]) : null;
        if (!$order) {
            $code = Response::HTTP_NOT_FOUND;
            $level = CouponNotification::LEVEL_WARNING;
            $reason = 'У текущего пользователя не найден ни один заказ';
        } else {
            $order->setDeliveryCostSum(0);
            $this->basketService->em->persist($order);
        }

        return [$code, $level, $reason];
    }

    /**
     * @param int $code
     * @param string $level
     * @param string $message
     * @param string $couponNumber
     * @return $this
     */
    private function addCouponNotification(int $code, string $level, string $message, string $reason, string $couponNumber): BaseController
    {
        $couponNotification = new CouponNotification();
        $couponNotification->setCode($code);
        $couponNotification->setLevel($level);
        $couponNotification->setMessage($message);
        $couponNotification->setReason($reason);
        $couponNotification->setCoupon($couponNumber);
        $this->basketService->couponNotificationCollection[] = $couponNotification;

        return $this;
    }

    /**
     * @param Request $request
     * @param $basketId
     * @return bool|Response
     */
    protected function noValidateCard(Request $request, $basketId)
    {
        if (!$basketId) {

            return $this->makeBadReqResp('basket ID required');
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $basket = $this->basketService->findOneBy(['id' => $basketId]);
        }else{
            $basket = $this->basketService->findOneBy(['id' => $basketId, 'active' => true]);
        }

        if (!$basket) {

            return $this->makeBasketNotFoundResp(__METHOD__, __LINE__, $basketId);
        }

        $requestBody = json_decode($request->getContent(), true);
        $card = ($requestBody and isset($requestBody['card'])) ? $requestBody['card'] : null;
        if (!$card || !$basket->checkCard($card)) {

            return $this->makeBasketBadReqResp(__METHOD__, __LINE__, $basketId, 'card ID wrong/required', $requestBody);
        }

        return false;
    }

    /**
     * @param Request $request
     * @param $basketId
     * @return Basket|object|Response
     */
    protected function noValidateCheckout(Request $request, $basketId)
    {
        if (!$basketId) {

            return $this->makeBadReqResp('basket ID required');
        }

        $basket = $this->basketService->findOneBy(['id' => $basketId, 'active' => true]);
        if (!$basket) {

            return $this->makeBasketNotFoundResp(__METHOD__, __LINE__, $basketId);
        }

        $userId = $request->get('user_id');
        if (!$userId) {

            return $this->makeBasketBadReqResp(__METHOD__, __LINE__, $basketId, 'user ID required');
        }

        return $basket;
    }

    /**
     * @param $userId
     * @return false|Response
     */
    protected function noValidateUserId($userId)
    {
        if (!$userId) {

            return $this->makeBadReqResp('user ID required');
        }

        return false;
    }

    /**
     * @param Request $request
     * @param $basketId
     * @return bool|Response
     */
    protected function noValidateRemove(Request $request, $basketId)
    {
        if (!$basketId) {

            return $this->makeBadReqResp('basket ID required');
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $basket = $this->basketService->findOneBy(['id' => $basketId]);
        }else{
            $basket = $this->basketService->findOneBy(['id' => $basketId, 'active' => true]);
        }

        if (!$basket) {

            return $this->makeBasketNotFoundResp(__METHOD__, __LINE__, $basketId);
        }

        $requestBody = json_decode($request->getContent(), true);
        $article = ($requestBody and isset($requestBody['article'])) ? $requestBody['article'] : null;
        if (!$article) {

            return $this->makeBasketBadReqResp(__METHOD__, __LINE__, $basketId, 'article not defined', $requestBody);
        }

        $item = $basket ? $this->itemService->findOneBy(['article' => $article, 'basketId' => $basket->getId()]) : null;
        if (!$item) {

            return $this->makeBasketNotFoundResp(__METHOD__, __LINE__, $basketId, "item {$article} not found", $requestBody);
        }

        return false;
    }

    /**
     * @param $basketId
     * @return Basket|Response
     */
    protected function noValidateClearBasket($basketId)
    {
        if (!$basketId) {

            return $this->makeBadReqResp('basket ID required');
        }

        $basket = $this->basketService->findOneBy(['id' => $basketId, 'active' => true]);
        if (!$basket) {

            return $this->makeBasketNotFoundResp(__METHOD__, __LINE__, $basketId);
        }

        return $basket;
    }

    /**
     * @param Request $request
     * @param $basketId
     * @return Basket|object|Response
     */
    protected function noValidatePayment(Request $request, $basketId)
    {
        if (!$basketId) {

            return $this->makeBadReqResp('basket ID required');
        }

        $basket = $this->basketService->findOneBy(['id' => $basketId, 'active' => true]);
        if (!$basket) {

            return $this->makeBasketNotFoundResp(__METHOD__, __LINE__, $basketId);
        }

        $requestBody = json_decode($request->getContent(), true);
        $paymentType = ($requestBody and isset($requestBody['payment_type'])) ? $requestBody['payment_type'] : null;
        if ($paymentType === null) {

            return $this->makeBasketBadReqResp(__METHOD__, __LINE__, $basketId, 'payment_type is null', $requestBody);
        }

        return $basket;
    }

    /**
     * @param Request $request
     * @param $basketId
     * @return Basket|object|Response
     */
    protected function noValidateAddItems(Request $request, $basketId)
    {
        if (!$basketId) {

            return $this->makeBadReqResp('basket ID required');
        }

        $basket = $this->basketService->findOneBy(['id' => $basketId, 'active' => true]);
        if (!$basket) {

            return $this->makeBasketNotFoundResp(__METHOD__, __LINE__, $basketId);
        }

        $requestBody = json_decode($request->getContent(), true);
        $itemData = ($requestBody and isset($requestBody['items'])) ? $requestBody['items'] : null;
        if ($this->basketService->isValidItemBarcodeData($itemData)) {

            return $this->makeBasketBadReqResp(__METHOD__, __LINE__, $basketId, 'item Data required', $requestBody);
        }

        return $basket;
    }

    /**
     * @param $basketId
     * @return bool|Response
     */
    protected function noValidateClearCard($basketId)
    {
        if (!$basketId) {

            return $this->makeBadReqResp('basket ID required');
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $basket = $this->basketService->findOneBy(['id' => $basketId]);
        }else{
            $basket = $this->basketService->findOneBy(['id' => $basketId, 'active' => true]);
        }

        if (!$basket) {

            return $this->makeBasketNotFoundResp(__METHOD__, __LINE__, $basketId);
        }

        return false;
    }

    /**
     * @param $number
     * @return Order|Response
     */
    protected function noValidateOrderInfo($number)
    {
        if (!$number) {

            return $this->makeBadReqResp('number required');
        }

        $order = $this->orderService->findOneBy(['orderId' => $number]);
        if (!$order) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number);
        }

        return $order;
    }

    public function noValidateOrderInfoByPartner($partnerSapId, $partnerOrderId)
    {
        if (!$partnerOrderId) {

            return $this->makeBadReqResp('number required');
        }

        $order = $this->orderService->findOneBy(['orderIdPartner' => $partnerOrderId, 'sourceIdentifier' => $partnerSapId]);

        if (!$order) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $partnerOrderId);
        }

        return $order;
    }


    /**
     * @param Request $request
     * @param $number
     * @return Order|object|Response
     */
    protected function noValidateOrderConfirm(Request $request, $number)
    {
        if (!$number) {

            return $this->makeBadReqResp('number required');
        }

        $order = $this->orderService->findOneBy(['orderId' => $number]);
        if (!$order) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number);
        }

        if ( $this->orderService->isOrderFinalStatus($order) ) {

            return $this->makeOrderFinalStatusResp(__METHOD__, __LINE__, $number);
        }

        $basket = $this->basketService->findOneBy(['orderId' => $order->getOrderId()]);
        if (!$basket) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number, 'basket not found');
        }

        return $order;
    }

    /**
     * @param Request $request
     * @param $number
     * @return Order|object|Response
     */
    protected function noValidateOrderConfirmDelivery(Request $request, $number)
    {
        if (!$number) {

            return $this->makeBadReqResp('number required');
        }

        $order = $this->orderService->findOneBy(['orderId' => $number]);
        if (!$order) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number);
        }

        if ( $this->orderService->isOrderFinalStatus($order) ) {

            return $this->makeOrderFinalStatusResp(__METHOD__, __LINE__, $number);
        }

        $basket = $this->basketService->findOneBy(['orderId' => $order->getOrderId()]);
        if ( !$basket || !$basket->isActive() ) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number, 'basket not found');
        }

        $requestBody = json_decode($request->getContent(), true);
        if (!$requestBody) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number, 'requestBody required');
        }

        if( isset($requestBody['delivery_scheme']) && !ShopConst::isValidDeliveryScheme($requestBody['delivery_scheme']) ) {

            return $this->makeOrderBadReqResp(__METHOD__, __LINE__, $number, 'wrong delivery_scheme', $requestBody);
        }

        if($order->getDeliveryType() == ShopConst::DELIVERY_KEY_TYPE_E) {
            if( empty($requestBody['delivery']['address']) ) {

                $this->makeOrderBadReqResp(__METHOD__, __LINE__, $number, 'delivery address required', $requestBody);
            }
        }

        return $order;
    }

    /**
     * @param Request $request
     * @param $number
     * @return Order|object|Response
     */
    protected function noValidateOrderConfirmCustomer(Request $request, $number)
    {
        if (!$number) {

            return $this->makeBadReqResp('number required');
        }

        $order = $this->orderService->findOneBy(['orderId' => $number]);
        if (!$order) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number);
        }

        if ( $this->orderService->isOrderFinalStatus($order) ) {

            return $this->makeOrderFinalStatusResp(__METHOD__, __LINE__, $number);
        }

        $basket = $this->basketService->findOneBy(['orderId' => $order->getOrderId()]);
        if ( !$basket || !$basket->isActive() ) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number,'basket not found');
        }

        $requestBody = json_decode($request->getContent(), true);
        if (!$requestBody) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number, 'requestBody required');
        }

        return $order;
    }

    /**
     * @param Request $request
     * @param $number
     * @return Order|object|Response
     */
    protected function noValidateOrderConfirmPayment(Request $request, $number)
    {
        $order = $this->noValidateOrderCommon($request, $number);
        if ($order instanceof Response) {

            return $order;
        }

        $basket = $this->basketService->findOneBy(['orderId' => $order->getOrderId()]);
        if ( !$basket || !$basket->isActive() ) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number, 'basket not found');
        }

        if ( $this->orderService->isOrderFinalStatus($order) ) {

            return $this->makeOrderFinalStatusResp(__METHOD__, __LINE__, $number);
        }

        return $order;
    }

    /**
     * @param Request $request
     * @param $number
     * @return Order|object|Response
     */
    protected function noValidateOrderConfirmPaymentInformation(Request $request, $number)
    {
        $requestBody = AppHelper::arrayFromJson($request->getContent());
        $paymentInformation = new PaymentInformation();
        $paymentInformation->status = $requestBody['status'] ?? null;
        $paymentInformation->amount = $requestBody['amount'] ?? null;
        $isValid = $this->isValidObject($paymentInformation);
        if(!$isValid['isValid']) {

            $this->makeOrderBadReqResp(__METHOD__, __LINE__, $number, $isValid['errors'], $requestBody);
        }

        $order = $this->noValidateOrderCommon($request, $number);
        if ($order instanceof Response) {

            return $order;
        }

        if(!$status = ShopConst::makeOrderStatusFromPaymentInformationStatus($requestBody['status'])) {

            return $this->makeOrderBadReqResp(__METHOD__, __LINE__, $number, 'wrong payment information status', $requestBody);
        }

        if ($order->getStatus() == ShopConst::STATUS_ISS && $status == ShopConst::STATUS_PYS) {

            return $this->makeOrderFinalStatusResp(__METHOD__, __LINE__, $number);
        }

        return $order;
    }

    /**
     * @param Request $request
     * @param $number
     * @return Order|object|Response
     */
    protected function noValidateOrderCommon(Request $request, $number)
    {
        if (!$number) {

            return $this->makeBadReqResp('number required');
        }

        $order = $this->orderService->findOneBy(['orderId' => $number]);
        if (!$order) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number);
        }

        $basket = $this->basketService->findOneBy(['orderId' => $order->getOrderId()]);
        if (!$basket) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number, 'basket not found');
        }

        $requestBody = AppHelper::arrayFromJson($request->getContent());
        if (!$requestBody) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number, 'requestBody required');
        }

        return $order;
    }

    /**
     * @param Request $request
     * @param $number
     * @return Order|object|Response
     */
    protected function noValidateOrderUpdateOrder(Request $request, $number)
    {
        if (!$number) {
            return $this->makeBadReqResp('number required');
        }
        $requestBody = json_decode($request->getContent(), true);
        if (!$requestBody) {
            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number, 'requestBody required');
        }
        $status = isset($requestBody['status']) ? strtoupper($requestBody['status']) : null;
        if ($status === null) {
            return $this->makeOrderBadReqResp(__METHOD__, __LINE__, $number, 'status not validate', $requestBody);
        }
        $overtimeDate = $requestBody['overtime_date'] ?? null;
        if ($overtimeDate != null) {
            $overtime = new Overtime();
            $overtime->date = $overtimeDate;
            $isValid = $this->isValidObject($overtime);
            if(!$isValid['isValid']) {

                return $this->makeOrderBadReqResp(__METHOD__, __LINE__, $number, 'overtime_date not validate', $requestBody);
            }
        }

        $order = $this->orderService->findOneBy(['orderId' => $number]);
        if (!$order) {
            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number);
        }
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            if ($status == $order->getStatus()) {
                $result = Response::HTTP_OK;
                $basket = $this->getBasketStoreId($order, $request);
                $out = [
                    'result' => $result,
                    'message' => 'status is equivalente',
                    'store_id' => $basket->getStoreId(),
                    'order_id' => $order->getOrderId(),
                    'order' => $order,
                ];
                return $this->handleView($this->view($out, $result));
            }
            if ($this->orderService->isOrderFinalStatus($order, $status)) {
                return $this->makeOrderFinalStatusResp(__METHOD__, __LINE__, $number);
            }
        }
        return $order;
    }

    /**
     * @param $out
     * @param $object
     * @param $title
     * @return Response
     */
    protected function returnOut($out, $object, $title)
    {
        $result = isset($out['result']) ? $out['result'] : Response::HTTP_BAD_REQUEST;
        $message = isset($out['message']) ? $out['message'] : 'undefined error on line ' . __LINE__ . ' for  method' .  __METHOD__;
        return $this->handleView($this->view($out, $result));

    }

    /**
     * @param Request $request
     * @param $number
     * @return Order|object|Response
     */
    protected function noValidateOrderUpdateGW(Request $request, $number)
    {
        if (!$number) {

            return $this->makeBadReqResp('number required');
        }

        $order = $this->orderService->findOneBy(['orderId' => $number]);
        if (!$order) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number);
        }

        if ( $this->orderService->isOrderFinalStatus($order) ) {

            return $this->makeOrderFinalStatusResp(__METHOD__, __LINE__, $number);
        }

        $requestBody = json_decode($request->getContent(), true);
        if (!$requestBody) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number, 'requestBody required');
        }

        $orderData = ($requestBody and isset($requestBody['order'])) ? $requestBody['order'] : null;
        if ($this->orderService->isValidOrderGWData($orderData)) {

            return $this->makeOrderBadReqResp(__METHOD__, __LINE__, $number, 'order data not validate', $requestBody);
        }

        $itemData = ($requestBody and isset($requestBody['items'])) ? $requestBody['items'] : null;
        if ($this->orderService->isValidItemGWData($itemData)) {

            return $this->makeOrderBadReqResp(__METHOD__, __LINE__, $number, 'items data required');
        }

        return $order;
    }

    /**
     * @param Request $request
     * @return bool|Response
     */
    protected function noValidateOrderSendGW(Request $request)
    {
        $requestBody = json_decode($request->getContent(), true);
        if (!$requestBody) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, null, 'requestBody required');
        }

        $ordersData = ($requestBody and isset($requestBody['orders'])) ? $requestBody['orders'] : null;
        if (!$ordersData) {

            return $this->makeOrderBadReqResp(__METHOD__, __LINE__, null, 'orders data required', $requestBody);
        }

        return false;
    }

    /**
     * @param $number
     * @return bool|Response
     */
    protected function noValidateOrderHistory($number)
    {
        if (!$number) {

            return $this->makeBadReqResp('number required');
        }

        $order = $this->orderService->findOneBy(['orderId' => $number]);
        if (!$order) {

            return $this->makeOrderNotFoundResp(__METHOD__, __LINE__, $number);
        }

        return false;
    }

    /**
     * @param $method
     * @param $line
     * @param $basketId
     * @param null $message
     * @param null $requestBody
     * @return Response
     */
    protected function makeBasketNotFoundResp($method, $line, $basketId = null, $message = null, $requestBody = null) {
        $message = [
            'message' => $message ?? 'basket not found',
            'basket_id' => $basketId,
            'request_body' => $requestBody ? AppHelper::jsonFromArray($requestBody) : null,
            'method' => $method,
            'line' => $line,
        ];

        return $this->makeResp($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * @param $method
     * @param $line
     * @param $basketId
     * @param null $message
     * @param null $requestBody
     * @return Response
     */
    protected function makeBasketBadReqResp($method, $line, $basketId = null, $message = null, $requestBody = null) {
        $message = [
            'message' => $message ?? 'bad request',
            'basket_id' => $basketId,
            'request_body' => $requestBody ? AppHelper::jsonFromArray($requestBody) : null,
            'method' => $method,
            'line' => $line,
        ];

        return $this->makeResp($message, Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param $method
     * @param $line
     * @param null $orderId
     * @param null $message
     * @return Response
     */
    protected function makeOrderNotFoundResp($method, $line, $orderId = null, $message = null) {
        $message = [
            'message' => $message ?? 'order not found',
            'order_id' => $orderId,
            'method' => $method,
            'line' => $line,
        ];

        return $this->makeResp($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * @param $method
     * @param $line
     * @param null $orderId
     * @param null $message
     * @param null $requestBody
     * @return Response
     */
    protected function makeOrderBadReqResp($method, $line, $orderId = null, $message = null, $requestBody = null) {
        $message = [
            'message' => $message ?? 'bad request',
            'order_id' => $orderId,
            'request_body' => $requestBody ? AppHelper::jsonFromArray($requestBody) : null,
            'method' => $method,
            'line' => $line,
        ];

        return $this->makeResp($message, Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param $method
     * @param $line
     * @param $orderId
     * @return Response
     */
    protected function makeOrderFinalStatusResp($method, $line, $orderId) {
        $message = [
            'message' => 'order status is final',
            'order_id' => $orderId,
            'method' => $method,
            'line' => $line,
        ];

        return $this->makeResp($message, Response::HTTP_CONFLICT);
    }

    protected function makeBadReqResp($message = null) {
        $message = [
            'message' => $message ?? 'bad request',
        ];

        return $this->makeResp($message, Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param $message
     * @param $respCode
     * @return Response
     */
    protected function makeResp($message, $respCode) {

        return $this->handleView(
            $this->view($message, $respCode)
        );
    }

    /**
     * @param Request $request
     * @return false|Response
     */
    protected function setStatusValidate(Request $request)
    {
        if ( !$request->get('orderId') ) {
            $this->logService->create(__METHOD__, json_encode(['message' => 'orderId required']), null, $request);

            return $this->makeBadReqResp();
        }

        if ( !$request->get('storeId') ) {
            $this->logService->create(__METHOD__, json_encode(['message' => 'storeId required']), null, $request);

            return $this->makeBadReqResp();
        }

        $requestBody = json_decode($request->getContent(), true);
        $status = new Status();
        $status->status = $requestBody['status'] ?? null;
        $isValid = $this->isValidObject($status);
        if(!$isValid['isValid']) {
            $this->logService->create(__METHOD__, json_encode($isValid['errors']), null, $request);
            return $this->makeBadReqResp();
        }

        return false;
    }


    /**
     * @param Request $request
     * @return false|Response
     */
    protected function getOrderValidate(Request $request)
    {
        if ( !$request->get('orderId') ) {
            $this->logService->create(__METHOD__, json_encode(['message' => 'orderId required']), null, $request);

            return $this->makeBadReqResp();
        }

        if ( !$request->get('storeId') ) {
            $this->logService->create(__METHOD__, json_encode(['message' => 'storeId required']), null, $request);

            return $this->makeBadReqResp();
        }

        return false;
    }

    /**
     * @param Request $request
     * @return false|Response
     */
    protected function createOrderValidate(Request $request)
    {
        if ( !$request->get('storeId') ) {
            $this->logService->create(__METHOD__, json_encode(['message' => 'storeId required']), null, $request);

            return $this->makeBadReqResp();
        }
        $requestBody = json_decode($request->getContent(), true);
        $customerReq = $requestBody['customer'] ?? null;
        $deliveryReq = $requestBody['delivery'] ?? null;
        $addressReq = $deliveryReq['address'] ?? null;
        $cityReq = $addressReq['city'] ?? null;
        $coordinatesReq = $addressReq['coordinates'] ?? null;
        $positionsReq = $requestBody['positions'] ?? null;

        $customer = new Customer();
        $customer->name = $customerReq['name'] ?? null;
        $customer->phone = $customerReq['phone'] ?? null;
        $isValid = $this->isValidObject($customer);
        if(!$isValid['isValid']) {
            $this->logService->create(__METHOD__, json_encode($isValid['errors']), null, $request);

            return $this->makeBadReqResp();
        }

        $city = new City();
        $city->name = $cityReq['name'] ?? null;
        $isValid = $this->isValidObject($city);
        if(!$isValid['isValid']) {
            $this->logService->create(__METHOD__, json_encode($isValid['errors']), null, $request);

            return $this->makeBadReqResp();
        }

        $coordinates = new Coordinates();
        $coordinates->latitude = $coordinatesReq['latitude'] ?? null;
        $coordinates->longitude = $coordinatesReq['longitude'] ?? null;
        $isValid = $this->isValidObject($coordinates);
        if(!$isValid['isValid']) {
            $this->logService->create(__METHOD__, json_encode($isValid['errors']), null, $request);

            return $this->makeBadReqResp();
        }

        $delivery = new Delivery();
        $delivery->expectedDateTime = $deliveryReq['expectedDateTime'] ?? null;
        $delivery->address = $addressReq ?? null;
        $isValid = $this->isValidObject($delivery);
        if(!$isValid['isValid']) {
            $this->logService->create(__METHOD__, json_encode($isValid['errors']), null, $request);

            return $this->makeBadReqResp();
        }

        $payment = new Payment();
        $payment->type = $requestBody['payment']['type'] ?? null;
        $isValid = $this->isValidObject($payment);
        if(!$isValid['isValid']) {
            $this->logService->create(__METHOD__, json_encode($isValid['errors']), null, $request);

            return $this->makeBadReqResp();
        }

        foreach ($positionsReq as $positionReq) {
            $position = new Position();
            $position->id = $positionReq['id'] ?? null;
            $position->quantity = $positionReq['quantity'] ?? null;
            $position->price = $positionReq['price'] ?? null;
            $position->discountPrice = $positionReq['discountPrice'] ?? null;
            $isValid = $this->isValidObject($position);
            if(!$isValid['isValid']) {
                $this->logService->create(__METHOD__, json_encode($isValid['errors']), null, $request);

                return $this->makeBadReqResp();
            }
        }

        $total = new Total();
        $total->totalPrice = $requestBody['total']['totalPrice'] ?? null;
        $total->discountTotalPrice = $requestBody['total']['discountTotalPrice'] ?? null;
        $total->deliveryPrice = $requestBody['total']['deliveryPrice'] ?? null;
        $isValid = $this->isValidObject($total);
        if(!$isValid['isValid']) {
            $this->logService->create(__METHOD__, json_encode($isValid['errors']), null, $request);

            return $this->makeBadReqResp();
        }

        $createOrder = new CreateOrder();
        $createOrder->originalOrderId = $requestBody['originalOrderId'] ?? null;
        $createOrder->customer = $customerReq ?? null;
        $createOrder->delivery = $deliveryReq ?? null;
        $createOrder->payment = $requestBody['payment'] ?? null;
        $createOrder->positions = $requestBody['positions'] ?? null;
        $createOrder->total = $requestBody['total'] ?? null;
        $createOrder->loyaltyCard = $requestBody['loyaltyCard'] ?? null;
        $isValid = $this->isValidObject($createOrder);
        if(!$isValid['isValid']) {
            $this->logService->create(__METHOD__, json_encode($isValid['errors']), null, $request);

            return $this->makeBadReqResp();
        }

        return false;
    }

    /**
     * @param $object
     * @return array
     */
    private function isValidObject($object) {
        $errors = $this->validator->validate($object);
        $isValid = count($errors) == 0;
        $errors = (string) $errors;

        return [
            'isValid' => $isValid,
            'errors'  => preg_replace("/\r|\n/", "", $errors),

        ];
    }

    /**
     * @param string $key
     * @param string|null $id
     * @return SemaphoreLocker|Response
     */
    protected function getLocker(string $key, string $id = null)
    {
        if ( empty($id) ) {

            return $this->makeResp(['message' => 'wrong request'], Response::HTTP_BAD_REQUEST);
        }
        $locker = $this->semaphoreLocker->lock($key,  $id);
        if( !$locker->acquire() ) {

            return $this->makeResp('double request', Response::HTTP_CONFLICT);
        }

        return $locker;
    }

    /**
     * @param string|null $basketId
     * @param string|null $orderId
     * @return SemaphoreLocker|Response
     */
    protected function initLocker(string $basketId = null, string $orderId = null)
    {
        $val = null;
        if( !empty($basketId) ) {
            $val = $basketId;
        } else {
            if( !empty($orderId) ) {
                if( $basket = $this->getBasketByOrderId($orderId) ) {
                    if( $basketId = $basket->getId() ) {
                        $val = $basketId;
                    }
                }
            }
        }

        return $this->getLocker($this->semaphoreKeyStage::COMMON, $val);
    }

    /**
     * @param string $orderId
     * @return Basket|object|null
     */
    protected function getBasketByOrderId(string $orderId)
    {
        return $this->basketService->findOneBy(['orderId' => $orderId]);
    }


}