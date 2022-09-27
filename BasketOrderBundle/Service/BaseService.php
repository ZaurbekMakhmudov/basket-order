<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 26.07.19
 * Time: 15:12
 */

namespace App\BasketOrderBundle\Service;

use App\BasketOrderBundle\Entity\Basket;
use App\BasketOrderBundle\Entity\Coupon;
use App\BasketOrderBundle\Entity\CouponRestriction;
use App\BasketOrderBundle\Entity\Item;
use App\BasketOrderBundle\Entity\Order;
use App\BasketOrderBundle\Era\EshopOrder;
use App\BasketOrderBundle\Era\EshopOrderPosition;
use App\BasketOrderBundle\Helper\AppHelper;
use App\BasketOrderBundle\Helper\DateTimeHelper;
use App\BasketOrderBundle\Helper\ItemHelper;
use App\BasketOrderBundle\Helper\SberMarketConst;
use App\BasketOrderBundle\Helper\ShopConst;
use App\BasketOrderBundle\Helper\SMMConst;
use App\BasketOrderBundle\Repository\ItemRepository;
use App\BasketOrderBundle\Traits\_LogTrait;
use App\CashboxBundle\Entity\Receipt;
use App\CashboxBundle\Service\Cashbox\CashboxService;
use App\CashboxBundle\Service\MailerError\MailerErrorService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Metaer\CurlWrapperBundle\CurlWrapper;
use Metaer\CurlWrapperBundle\CurlWrapperException;
use Doctrine\Persistence\ManagerRegistry;
use OpenTracing\GlobalTracer;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\User;
use ulibkaradugi\IntegrationBundle\Facade\TraceFacade;
use WebPlatform\InGatewayBundle\Communicator\Communicator;
use App\BasketOrderBundle\Entity\OrderHistory;
use App\BasketOrderBundle\Traits\_EshopServiceTrait;
use Psr\Container\ContainerInterface;

class BaseService
{
    use _LogTrait;
    use _EshopServiceTrait;
    public $errors = [];
    protected $cashboxShop;
    protected $nameLogFile;
    /** @var  boolean */
    protected $confirm = false;
    /** @var  User */
    protected $user;
    protected $action;
    /**
     * @var LogService
     */
    protected LogService $logService;

    /** @var \Doctrine\Common\Persistence\ObjectRepository|ItemRepository */
    public $repoItem;

    protected $cashboxScriptUrl;
    /**
     * @var CurlWrapper
     */
    protected $curlWrapper;
    /**
     * @var DelayService
     */
    protected $delayService;

    protected $costDeliveryExcludedDiscountCodes;
    /**
     * @var CashboxService
     */
    protected CashboxService $cashbox;

    protected MailerErrorService $mailer;

    protected ContainerInterface $container;

    protected $messageController;

    public Serializer $serializer;

    private bool $isUseCasheBox = true;

    protected $eventParams;

    public $couponNotificationCollection;

    private $cashboxResendCases;

    private $client;

    /**
     * BaseService constructor.
     * @param CashboxService $cashbox
     * @param DelayService $delayService
     * @param CurlWrapper $curlWrapper
     * @param ManagerRegistry $doctrine
     * @param null $cashboxShop
     */
    function __construct(
        CashboxService $cashbox,
        DelayService $delayService,
        CurlWrapper $curlWrapper,
        ManagerRegistry $doctrine,
        $cashboxShop = null
    )
    {
        $this->doctrine = $doctrine;
        $this->em = $doctrine->getManager();
        $this->emEra = $doctrine->getManager('era');
        $this->repoOrder = $this->em->getRepository(Order::class);
        $this->repoOrderHistory = $this->em->getRepository(OrderHistory::class);
        $this->repoItem = $this->em->getRepository(Item::class);
        $this->repoBasket = $this->em->getRepository(Basket::class);
        $this->repoEshoOrder = $this->emEra->getRepository(EshopOrder::class);
        $this->repoEshoOrderPosition = $this->emEra->getRepository(EshopOrderPosition::class);
        $this->cashboxShop = $cashboxShop;
        $this->curlWrapper = $curlWrapper;
        $this->delayService = $delayService;
        $this->cashbox = $cashbox;
        $this->serializer = (new SerializerBuilder())->build();
        $this->couponNotificationCollection = new ArrayCollection();
        $this->client = HttpClient::create();
    }

    public function setVars(
        $container,
        $mailer,
        $messageController,
        $eventParams,
        $logService
    )
    {
        $this->mailer = $mailer;
        $this->container = $container;
        $this->messageController = $messageController;
        $this->eventParams = $eventParams;
        $this->logService = $logService;
    }

    public function setCashboxVars($cashboxResendCases)
    {
        $this->cashboxResendCases = $cashboxResendCases;
    }

    /**
     * @param bool $isUseCasheBox
     * @return $this
     */
    public function setIsUseCasheBox(bool $isUseCasheBox)
    {
        $this->isUseCasheBox = $isUseCasheBox;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsUseCasheBox()
    {

        return $this->isUseCasheBox;
    }

    /**
     * @param  $costDeliveryExcludedDiscountCodes
     * @return $this
     */
    public function setCostDeliveryExcludedDiscountCodes($costDeliveryExcludedDiscountCodes)
    {
        $this->costDeliveryExcludedDiscountCodes = $costDeliveryExcludedDiscountCodes;

        return $this;
    }

    /**
     * @param $user
     * @return $this
     */
    public function initUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return bool
     */
    public function isConfirm()
    {
        return $this->confirm;
    }

    /**
     * @param Order $order
     */
    public function setOnConfirm(Order $order)
    {
        $status = $order->getStatus();
        if ($status == ShopConst::STATUS_DRAFT || $status == ShopConst::STATUS_OPC || $status == ShopConst::STATUS_FFM) {
            $order->setConfirm(true);
            $this->confirm = true;
        }
    }

    /**
     * @param $confirm
     */
    public function setConfirm($confirm)
    {
        $this->confirm = $confirm;
    }

    /**
     * @param $nameLogFile
     */
    public function setNameLogFile($nameLogFile)
    {
        $this->nameLogFile = $nameLogFile;
    }

    /**
     * @param Basket|Item|Order|EshopOrder $entity
     * @param null $em
     */
    public function fUpdate($entity, $em = null)
    {
        if (method_exists($entity, 'setUpdated')) {
            $entity->setUpdated(DateTimeHelper::getInstance()->getDateCurrent());
        }
        $this->_persist($entity, $em);
        $this->_flush($em);
    }

    /**
     * @param Basket|Item|Order $entity
     * @param null $em
     */
    public function fPersist($entity, $em = null)
    {
        $this->_persist($entity, $em);
        $this->_flush($em);
    }

    /**
     * @param null $em
     */
    public function _flush($em = null)
    {
        if ($em == 'era') {
            $this->emEra->flush();
        } else {
            $this->em->flush();
        }
    }

    /**
     * @param $entity
     * @param null $em
     */
    public function _persist($entity, $em = null)
    {
        if ($em == 'era') {
            $this->emEra->persist($entity);
        } else {
            $this->em->persist($entity);
        }
    }

    private function getCashboxResendCase($cashboxHttpCode, $cashboxError)
    {
        foreach ($this->cashboxResendCases as $cashboxResendCase) {
            $caseMaxTry   = $cashboxResendCase[0];
            $caseHttpCode = $cashboxResendCase[1];
            $caseError    = $cashboxResendCase[2];
            if($caseHttpCode == $cashboxHttpCode && $caseError == $cashboxError) {

                return $cashboxResendCase;
            }
        }

        return false;
    }

    /**
     * @param int $try
     * @param string $method
     * @param Basket $basket
     * @param array $postData
     * @param bool $istotal
     * @return array|null[]
     */
    private function tryCallCashBoxDirectly(int $try, string $method, Basket $basket, array $postData, bool $istotal): array
    {
        $cashboxResponse = $this->callCashBoxDirectly($postData, $istotal);
        $output = $cashboxResponse['body'];
        $code = $output['code'] ?? null;
        $message = $output['message'] ?? null;

        return [$output, $code, $message];
    }

    /**
     * @param Basket $basket
     * @param $postData
     * @param Order|null $order
     * @param bool $doCalculateForced
     * @return array|mixed
     */
    private function callCashBox(Basket $basket, $postData, Order $order = null, $doCalculateForced = false)
    {
        $istotal = $doCalculateForced ? false : $this->doGenerate($order);
        $method = $istotal ? 'generate' : 'calculate';
        $title = 'send-cashbox';
        $this->delayService->initDelay($title);
        try {
            $try = 1;
            list($output, $codeCashbox, $message) = $this->tryCallCashBoxDirectly($try, $method, $basket, $postData, $istotal);
            if($order) {
                $istotal ? $order->setGenerate(new \DateTime()) : $order->setCalculate(new \DateTime());
                $this->_persist($order);
            }
            if( $cashboxResendCase = $this->getCashboxResendCase($codeCashbox, $message) ) {
                $caseMaxTry = $cashboxResendCase[0];
                for($try = 2; $try <= $caseMaxTry; $try++) {
                    list($output, $codeCashbox, $message) = $this->tryCallCashBoxDirectly($try, $method, $basket, $postData, $istotal);
                    if( !$cashboxResendCase = $this->getCashboxResendCase($codeCashbox, $message) ) {

                        break;
                    }
                }
            }
        } catch (Exception $e) {
            $this->delayService->finishDelay($basket->getId(), $title);
            $error = $e->getMessage();
            $result = Response::HTTP_INTERNAL_SERVER_ERROR;
            $out = [
                'result' => $result,
                'message' => 'curl request error',
                'basket_id' => $basket->getId(),
                'error' => $error,
                'option' => $postData,
                'doExit' => true,
            ];
            $this->cashbox->createCashboxError(Request::createFromGlobals(), $this->container->get('shop.mailer'));
            return $out;

        }
        $this->delayService->finishDelay($basket->getId(), $title);
        if ($codeCashbox) {
            if ($codeCashbox == Response::HTTP_BAD_GATEWAY || $codeCashbox == Response::HTTP_BAD_REQUEST ) {
                $out = [
                    'result' => $codeCashbox,
                    'basket_id' => $basket->getId(),
                    'message' => $message,
//                    'doTryWithoutCard' => true,
                    'doExit' => true,
                ];

                return $out;
            } elseif ($codeCashbox != Response::HTTP_OK) {
                $out = [
                    'result' => $codeCashbox,
                    'basket_id' => $basket->getId(),
                    'message' => $message,
                    'doExit' => true,
                ];

                return $out;
            }
        }

        return $output;
    }

    /**
     * @param array $postData
     * @param bool $istotal
     * @return array
     */
    protected function callCashBoxDirectly(array $postData, bool $istotal)
    {
        $this->cashbox->setReceipt(json_encode($postData))->request($this->cashboxShop, $istotal)->saveResponse($this->em);
        if( is_null($this->cashbox->getError()) ) {
            $code = Response::HTTP_OK;
            $body = $this->cashbox->getReceiptContent();
        } else {
            $code = $this->cashbox->getError()->getErrorCode();
            $body = $this->cashbox->getErrorContent();
            $this->cashbox->createCashboxError(Request::createFromGlobals(), $this->container->get('cashbox.mailer'));
        }
        $this->cashbox->setError(null);

        return [
            'code' => $code,
            'body' => $body,
        ];
    }

    /**
     * @param Basket $basket
     * @param $items
     * @param Order $order
     * @return false|mixed
     */
    public function sendCashBoxReadOnly(Basket $basket, $items, Order $order = null)
    {
        $postData = ItemHelper::getItemByCashbox($basket, $items, $this->cashboxShop);
        $postData['identifier'] = ShopConst::genHash($postData);
        $out = $this->callCashBox($basket, $postData, $order, true);
        if( empty($out['doExit']) ) {

            return $out ?? false;
        } else {

            return false;
        }
    }

    /**
     * @param Basket $basket
     * @param $items
     * @param Order|null $order
     * @param false $sendForced
     * @param Receipt|null $receipt
     * @return array|string
     */
    public function sendCashBox(Basket $basket, $items, Order $order = null, $sendForced = false, Receipt $receipt = null)
    {
        $postData = ItemHelper::getItemByCashbox($basket, $items, $this->cashboxShop);
        $basketId = $basket->getId();
        if (!$postData) {
            $out = [
                'result' => Response::HTTP_BAD_REQUEST,
                'basket_id' => $basketId,
                'message' => 'postData is null',
            ];

            return $out;
        }
        $isConfirm = $this->isConfirm();
        $hashUniq = ShopConst::genHash($postData);
        $identifier = $basket->getIdentifier();
        if (!$sendForced && $hashUniq == $identifier and !$isConfirm) {
            $out = [
                'result' => Response::HTTP_OK,
                'message' => 'hash item is equivalent or no confirm',
                'basket_id' => $basketId,
                'articles' => $items,
            ];

            return $out;
        }
        $postData['identifier'] = $hashUniq;


        if (!$receipt) {
            $output = $this->callCashBox($basket, $postData, $order);
        } else {
            $output = $this->cashbox->setReceiptObject($receipt)->vicherk($receipt, $order)->getReceiptContent();
        }

        if( !empty($output['doExit']) ) {

            return $output;
        }
        if( !empty($postData['cardNumber']) && !empty($output['doTryWithoutCard']) ) {
            unset($postData['cardNumber']);
            $output = $this->callCashBox($basket, $postData, $order);
            if( !empty($output['doExit']) || !empty($output['doTryWithoutCard']) ) {

                return $output;
            }
            $basket->setWithoutCard(true);
        }

        $itemsOut = ItemHelper::getItemsOut($output);
        if (!$itemsOut) {
            $out = [
                'result' => Response::HTTP_BAD_REQUEST,
                'basket_id' => $basketId,
                'message' => 'items is null',
            ];

            return $out;
        }

        $basketIdOut = isset($output['basketId']) ? $output['basketId'] : null;
        if ($basketIdOut and $basketId != $basketIdOut) {
            $out = [
                'result' => Response::HTTP_BAD_REQUEST,
                'basket_id' => $basketId,
                'message' => 'basket Id in cashbox incorrect',
            ];

            return $out;
        }
        $basket->setCashboxResponse(json_encode($output));
        $this->doGenerate($order) ? $basket->setSoftCheque($output['identifier']) : null;
        $articles = [];
        /** @var Item $item */
        foreach ($items as $item) {
            $article = $item->getArticle();
            $cost = (float)$item->getCost();
            $qty = $item->getQuantity();
            $key = ItemHelper::getKeyItem($article, $cost);

            if($qty==0){
                $this->_persist($item);
                $articles[] = $item;

                continue;
            }

            $itemOut = isset($itemsOut[$key]) ? $itemsOut[$key] : null;
            if ($itemOut) {
                $item->setItemCashbox($itemOut);
                $this->_persist($item);
                $articles[] = $item;
            }else{
                $item->setBasketId(0);
                $this->_persist($item);
            }
            unset($itemsOut[$key]);

        }

        if ($itemsOut) {
            foreach ($itemsOut as $itemOut) {
                $itemNew = new Item();
                $itemNew->setBasketId($basketId);
                $itemNew->setItemCashbox($itemOut);
                $this->_persist($itemNew);
                $articles[] = $itemNew;
            }
        }

        $basket->updateBasketPrice($articles, $this->costDeliveryExcludedDiscountCodes);
        $basket->setOutCashbox($output);
        $actions = $this->collectBasketActions($basket);
        $basket->setActions($actions);
        $this->_persist($basket);
        $this->updateDiscountName($basket, $items);
        $this->updateItemActions($items);
        $orderMessage = $basket->getOrderId() ? '/order ' . $basket->getOrderId() : null;
        $result = Response::HTTP_OK;
        $message = 'basket ' . $basketId . $orderMessage . ' from cashbox updated';

        $out = [
            'result' => $result,
            'message' => $message,
            'basket_id' => $basketId,
            'articles' => $items,
        ];

        return $out;

    }

    /**
     * @param Order|null $order
     * @return bool
     */
    protected function doGenerate(Order $order = null)
    {
        if (
            $order != null
            && ShopConst::isDeliveryTypeRM( $order->getDeliveryType() )
            && $order->getPaymentType()  == ShopConst::PAYMENT_KEY_TYPE_O
        ) {
            $onlinePickup = true;
        } else {
            $onlinePickup = false;
        }

        return ($this->isConfirm() && $order->getPaymentType()  == ShopConst::PAYMENT_KEY_TYPE_C);

        return ($this->isConfirm() && !$onlinePickup);
    }

    /**
     * @param Basket $basket
     */
    public function setBasketInActive(Basket $basket)
    {
        if ($basket) {
            $basket->setActive(false);
            $this->fUpdate($basket);
        }
    }

    /**
     * @param Basket $basket
     * @param Order|null $order
     * @param null $userId
     */
    public function updateOrderForCheckout(Basket $basket, Order $order = null, $userId = null)
    {
        if (!$order) {
            /** @var Order $order */
            $order = $this->repoOrder->findOneBy(['orderId' => $basket->getOrderId()]);
        }
        if ($order) {
            $order->setActions( $this->collectBasketActions($basket) );
            $order->setPrice($basket->getPrice());
            $order->setCost($basket->getCost());
            $userId ? $order->setUserId($userId) : null;
            $this->_persist($order);
        }
    }

    /**
     * @param Basket $basket
     * @return array
     */
    public function collectBasketActions(Basket $basket)
    {
        $actions = [];
        $coupons = $basket->getCoupons();
        if($coupons != null) {
            foreach ($coupons as $key => $coupon) {
                $actions['coupons'][] = [
                    'code' => $coupon['number'],
                    'name' => ShopConst::findCouponOnlineTitle($coupon['number']) ?? $_ENV['promo_couponname_cap'],
                ];
            }
        }
        return $actions;
    }

    /**
     * @param $itemsData
     * @return bool
     */
    public function isValidItemData($itemsData)
    {
        $out = (boolean)$this->errors;
        if ($itemsData) {
            $template = ['name', 'price', 'quantity', 'weight', 'article', 'volume'];
            foreach ($itemsData as $items) {
                $keys = array_keys($items);
                $is = array_diff($template, $keys);
                if ($is) {

                    $this->errors['template'] = 'into items not fields: ' . implode(',', $is);
                }
                if (!$this->errors and $items) {
                    foreach ($items as $key => $item) {
                        if (in_array($key, ['name', 'price', 'quantity', 'weight', 'article', 'volume'])) {
                            if (mb_strlen($item) == 0) {
                                $this->errors[$key] = $key . ' item required';
                            }
                            if($key == 'quantity' && $item < 0) {
                                $this->errors[$key] = $key . ' is negative';
                            }
                        }
                    }
                } else {
                    $this->errors[] = 'itemsData required';
                }
                $out = (boolean)$this->errors;
            }
        } else {
            $this->errors[] = 'itemsData is null';
            $out = (boolean)$this->errors;
        }

        return $out;
    }

    /**
     * @param $itemsData
     * @return bool
     */
    public function isValidItemBarcodeData($itemsData)
    {
        $out = (boolean)$this->errors;
        if ($itemsData) {
            $template = ['quantity', 'barcode'];
            foreach ($itemsData as $items) {
                $keys = array_keys($items);
                $is = array_diff($template, $keys);
                if ($is) {

                    $this->errors['template'] = 'into items not fields: ' . implode(',', $is);
                }
                if (!$this->errors and $items) {
                    foreach ($items as $key => $item) {
                        if (in_array($key, ['quantity', 'barcode'])) {
                            if (mb_strlen($item) == 0) {
                                $this->errors[$key] = $key . ' item required';
                            }
                        }
                    }
                } else {
                    $this->errors[] = 'itemsData required';
                }
                $out = (boolean)$this->errors;
            }
        } else {
            $this->errors[] = 'itemsData is null';
            $out = (boolean)$this->errors;
        }

        return $out;
    }

    /**
     * @param $itemsData
     * @return bool
     */
    public function isValidItemRemanagerData($itemsData)
    {
        $out = (boolean)$this->errors;
        if ($itemsData) {
            $templates = ['quantity', 'article'];
            foreach ($itemsData as $items) {
                $keys = array_keys($items);
                $is = array_diff($templates, $keys);
                if ($is) {

                    $this->errors['template'] = 'into items not fields: ' . implode(',', $is);
                }
                if (!$this->errors and $items) {
                    foreach ($items as $key => $item) {
                        if (in_array($key, $templates)) {
                            if (mb_strlen($item) == 0) {
                                $this->errors[$key] = $key . ' item required';
                            }
                        }
                    }
                } else {
                    $this->errors[] = 'itemsData required';
                }
                $out = (boolean)$this->errors;
            }
        } else {
            $this->errors[] = 'itemsData is null';
            $out = (boolean)$this->errors;
        }

        return $out;
    }

    /**
     * @param Basket $basket
     * @param $itemsData
     * @return array|null
     */
    public function addItems(Basket $basket, $itemsData, $issetDiscounts = false)
    {
        $items = $this->repoItem->findBy(['basketId' => $basket->getId(),],['article'=>'asc', 'cost'=>'asc']);
        if ($itemsData) {
            foreach ($itemsData as $itemData) {
                $item = ItemHelper::addItems($itemData, $items);
                if ($item) {
                    $item->addQuantity($itemData['quantity']);
                } else {
                    $item = $this->createItem($basket, $itemData, $issetDiscounts);
                    $items[] = $item;
                }
                $item->setOriginalQuantity($item->getQuantity());
                $this->_persist($item);
            }
        }
        $basket->updateBasketPrice($items, $this->costDeliveryExcludedDiscountCodes);
        $this->_persist($basket);

        return $items;
    }

    /**
     * @param Basket $basket
     * @param $itemData
     * @return Item
     */
    private function createItem(Basket $basket, $itemData, $issetDiscounts = false)
    {
        $item = new Item();
        $item->setBasketId($basket->getId());
        $item->addItem($itemData, $issetDiscounts);

        return $item;
    }

    /**
     * @param Basket $basket
     * @param $article
     * @param $itemQty
     * @return Item[]|\object[]
     */
    public function updateCounters(Basket $basket, $article, $itemQty)
    {
        $items = $this->repoItem->findBy(['basketId' => $basket->getId(),]);
        /** @var Item $item */
        foreach ($items as $key => $item) {
            $itemArticle = $item->getArticle();
            $itemBarcode = $item->getBarcode();
            $cost = (float)$item->getCost();
            $itemArticle = $itemArticle ? $itemArticle : $itemBarcode;
            if($article == $itemArticle){
                if($cost ==0){
                    $item->setBasketId(0);
                    unset($items[$key]);
                }else{
                    $item->setQuantity($itemQty);
                    $item->setCost($itemQty * $item->getPrice());
                }
                $item->setOriginalQuantity($item->getQuantity());
                $this->_persist($item);
            }
        }
        $basket->updateBasketPrice($items, $this->costDeliveryExcludedDiscountCodes);
        $this->_persist($basket);

        return $items;
    }

    /**
     * @return Order
     */
    public function createOrder()
    {
        $order = new Order();
        $order->setOrderId($this->setOrderNumber());

        return $order;
    }

    /**
     * Номер заказа
     * Формата: UR- Year Rand - max
     * Пример: UR-19100-1
     *
     * @return string
     */
    private function setOrderNumber()
    {
        $out = 'UR-' . vsprintf('%d%d-%d', [
                DateTimeHelper::getInstance()->getDateYear(null, 2),
                mt_rand(100, 999),
                $this->repoOrder->findByNumberMax() + 1,
            ]);

        return $out;
    }

    /**
     * @param Basket $basket
     * @param $article
     * @param $items
     * @return mixed
     */
    public function removeItem(Basket $basket, $article, $items)
    {
        /** @var Item $item */
        foreach ($items as $key => $item) {
            $articleItem = $item->getArticle();
            $barcodeItem = $item->getBarcode();
            $articleItem = $articleItem ? $articleItem : $barcodeItem;
            if ($article == $articleItem) {
                $item->setBasketId(0);
                unset($items[$key]);
            }
            $this->_persist($item);
        }
        $basket->updateBasketPrice($items, $this->costDeliveryExcludedDiscountCodes);
        $this->_persist($basket);

        return $items;
    }

    /**
     * @param Order $order
     * @return array|bool
     */
    protected function isOrderErrors(Order $order)
    {
        if (!empty($this->errors)) {
            $result = Response::HTTP_BAD_REQUEST;
            $message = 'order no confirm, because is errors';
            $out = [
                'result' => $result,
                'message' => $message,
                'order_id' => $order->getOrderId(),
                'errors' => $this->errors,
            ];

            return $out;
        }

        return false;
    }

    /**
     * @param Request $request
     * @param Order $order
     * @param Basket $basket
     * @return array
     */
    protected function getOrderItems(Request $request, Order $order, Basket $basket)
    {
        $out = null;
        $orderItems = ($basket and $basket instanceof Basket) ? $this->repoItem->findBy(['basketId' => $basket->getId()]) : null;
        if (!$orderItems) {
            $this->errors[] = 'items is empty';
            $result = Response::HTTP_BAD_REQUEST;
            $requestBody = json_decode($request->getContent(), true);
            $message = 'order update, but exist errors';
            $out = [
                'result' => $result,
                'message' => $message,
                'order_id' => $order->getOrderId(),
                'request_body' => AppHelper::jsonFromArray($requestBody),
                'errors' => AppHelper::jsonFromArray($this->errors),
            ];
        }

        return [$orderItems, $out];
    }

    /**
     * @param Order $order
     * @return array|false
     */
    protected function isStatusDraft(Order $order)
    {
        if ($order->getStatus() != ShopConst::STATUS_DRAFT) {
            $result = Response::HTTP_BAD_REQUEST;
            $message = 'draft order has already been sent';
            $out = [
                'result' => $result,
                'message' => $message,
                'order_id' => $order->getOrderId(),
            ];

            return $out;
        }

        return false;
    }

    /**
     * @param Order $order
     * @param Basket $basket
     * @return array|bool
     */
    protected function isSetStatusByPaymentType(Order $order, Basket $basket)
    {
        if ($order->getPaymentType() == ShopConst::PAYMENT_KEY_TYPE_C) {
            $order->setStatus(ShopConst::STATUS_PCRE);
        } elseif ( $order->getPaymentType() == ShopConst::PAYMENT_KEY_TYPE_O ) {
            $order->setStatus(ShopConst::STATUS_ONL);
        } else {
            $result = Response::HTTP_BAD_REQUEST;
            $message = 'unknown payment type';
            $out = [
                'result' => $result,
                'message' => $message,
                'order_id' => $order->getOrderId(),
            ];

            return $out;
        }

        return false;
    }

    /**
     * @param Order $order
     * @param Basket|null $basket
     * @return bool
     */
    public function isCouponForPayment(Order $order, Basket $basket = null): bool
    {
        $out = false;
        $status = $order->getStatus();
        $paymentType = $order->getPaymentType();
        if($status != ShopConst::STATUS_DRAFT) {

            return false;
        }
        if ($paymentType == ShopConst::PAYMENT_KEY_TYPE_O) {
            if ($basket === null) {
                $basket = $this->repoBasket->findOneBy(['orderId' => $order->getOrderId()]);
            }
            if( $this->isUseCasheBox($order) ) {
//                $basket->addCoupon($this->getCouponOnline()['number']);
            }
            $out = true;
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
            $out = true;
        }

        return $out;
    }

    /**
     * @param Order $order
     * @param Basket $basket
     * @param bool $out
     * @return array|bool
     */
    protected function isSendCashBox(Order $order, Basket $basket, $out = false)
    {
        $isSendCashBox = $out ? $out : $order->isConfirm();
        if ( $this->isUseCasheBox($order) && $isSendCashBox ) {
            $items = $this->repoItem->findBy(['basketId' => $basket->getId()]);
            $out = $this->sendCashBox($basket, $items, $order);
            $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
            if ($result != Response::HTTP_OK) {
                $message = $out['message'] ?? 'undefined error on line ' . __LINE__ . ' for  method' . __METHOD__;
                $out = [
                    'result' => $result,
                    'message' => $message,
                    'order_id' => $order->getOrderId(),
                ];

                return $out;
            }
        }

        return false;
    }

    /**
     * @param Order $order
     * @param Basket $basket
     * @return array|bool
     */
    protected function responseConfirm(Order $order, Basket $basket, $items)
    {
        if (empty($this->errors)) {
            $message = 'order options update';
        } else {
            $message = 'order part options update, but exist errors in another options order\'s';
        }
        $result = Response::HTTP_OK;
        $this->updateOrderForCheckout($basket);
        $this->_flush();
        $out['result'] = $result;
        $out['message'] = $message;
        $out['store_id'] = $basket->getStoreId();
        $this->errors ? $out['errors'] = $this->errors : null;
        $out['order_id'] = $order->getOrderId();
        $out['order'] = $order; //->iterateVisible();
        $out['basket'] = $basket;
        $out['items'] = $items;


        return $out;
    }

    /**
     * @param Communicator|null $communicator
     * @param Order|null $order
     * @param String|null $url
     * @param array|null $data
     * @param bool $toRM
     * @param int|null $historyOrderId
     * @return array
     */
    public function sendToCommunicator(Communicator $communicator = null, Order $order = null, String $url = null, Array $data = null, bool $toRM = false, int $historyOrderId = null): array
    {
        if($toRM) {
            $this->makeEvent($order, $data, $this->eventParams[$this->action], $historyOrderId);
            return ['result' =>  Response::HTTP_OK];
        }
        $data = ItemHelper::communicatorData($order, $data);
        $url = $url ?? ShopConst::COMMUNICATOR_SCRIPT;
        $spanContext = GlobalTracer::get()->getActiveSpan()->getContext();
        $span = GlobalTracer::get()->startSpan("POST $url", ['child_of' => $spanContext]);
        try {
            $response = $communicator->send($url, $data);
            $responseStatus = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $order->setComment($responseStatus);
            $result = Response::HTTP_OK;
            $message = 'order send communicator';
        } catch (Exception $e) {
            $error = $e->getMessage();
            $result = Response::HTTP_INTERNAL_SERVER_ERROR;
            $message = 'unknown error send order to communicator';
            TraceFacade::log($span, [
                'exception' => $e->getMessage()
            ]);
            TraceFacade::finish($span);
        }
        $out['order_id'] = $order->getOrderId();
        $out['result'] = $result;
        $out['url'] = $url;
        $out['data'] = $data;
        $out['message'] = $message;
        isset($responseStatus) ? $out['status_communicator'] = $responseStatus : null;
        isset($body) ? $out['body'] = $body : null;
        isset($error) ? $out['errors'] = $error : null;

        TraceFacade::log($span, $out);
        TraceFacade::finish($span);

        return $out;
    }

    /**
     * @param Order $order
     * @param array $eventBody
     * @param string|null $eventName
     * @param int|null $historyOrderId
     */
    public function makeEvent(Order $order, array $eventBody, string $eventName = null, int $historyOrderId = null)
    {
        list($eventName, $workflowId) = $this->messageController->createEvent($eventBody, $eventName, $historyOrderId);
        $this->logService->create(__METHOD__, ['eventName' => $eventName, 'workflowId' => $workflowId]);
    }

    /**
     * @param array $workflowIds
     * @return array
     */
    public function sendEvents(array $workflowIds): array
    {
        $result = $this->messageController->sendEvents($workflowIds) ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR;

        return [
            'result' => $result,
        ];
    }

    /**
     * @param Order $order
     * @param $xmlContent
     * @return array
     */
    protected function createPostData(Order $order, $xmlContent)
    {
        $post['xml'] = $xmlContent;
        if ($this->action == ShopConst::GW_ES_ORDER_CREATE) {
            $post['delivery_type'] = $order->getDeliveryType();
        }

        return $post;
    }

    /**
     * @param $itemsData
     * @return bool
     */
    public function isValidItemGWData($itemsData)
    {
        $out = (boolean)$this->errors;
        if ($itemsData) {
            $templates = ['product_id', 'product_amount','product_unit_price'];
            foreach ($itemsData as $items) {
                $keys = array_keys($items);
                $is = array_diff($templates, $keys);
                if ($is) {

                    $this->errors['template'] = 'into items not fields: ' . implode(',', $is);
                }
                if (!$this->errors and $items) {
                    foreach ($items as $key => $item) {
                        if (in_array($key, $templates)) {
                            if (mb_strlen($item) == 0) {
                                $this->errors[$key] = $key . ' item required';
                            }
                        }
                    }
                } else {
                    $this->errors[] = 'itemsData required';
                }
                $out = (boolean)$this->errors;
            }
        }

        return $out;
    }

    /**
     * @param $order
     * @return bool
     */
    public function isValidOrderGWData($order)
    {
        $status = isset($order['order_status']) ? $order['order_status'] : null;
        if(!$status){

            return true;
        }
        $sum = isset($order['order_sum']) ? $order['order_sum'] : null;
        if(!$sum){

//            return true;
        }

        return false;
    }

    /**
     * @param Order $order
     * @param Basket $basket
     * @param string|null $status
     * @return int|null
     */
    public function insertIntoOrderHistory(Order $order, Basket $basket, string $status = null): ?int
    {
        $userName = $this->logService->user ? $this->logService->user->getUsername() : '';
        $orderHistory = new OrderHistory();
        $orderHistory->setOrderId($order->getOrderId());
        $orderHistory->setPrice($order->getPrice());
        $orderHistory->setCost($order->getCost());
        $orderHistory->setCostDelivery($basket->getCostDelivery());
        $orderHistory->setStatus( $status ?? $order->getStatus() );
        $orderHistory->setUser($userName);
        $orderHistory->setInserted(new \DateTime());
        $order->getCalculate() ? $orderHistory->setCalculate($order->getCalculate()) : null;
        $order->getGenerate() ? $orderHistory->setGenerate($order->getGenerate()) : null;
        $this->_persist($orderHistory);
        $this->_flush();

        return $orderHistory->getId();
    }

    /**
     * @param Order $order
     * @param Basket $basket
     * @param Communicator $communicator
     * @param $in
     */
    public function sendCommunicatorErrorNotify(Order $order, Basket $basket, Communicator $communicator, $in) {
        $title = 'send-communicator-notify';
        $this->delayService->initDelay($title);
        $uri = '/system-notification/error/create';
        $data = [
            'doc' => [
                'status_code' => $in['result'],
                'context' => $in,
            ],
            'params' => [
                'sender' => 'shop.api'
            ]
        ];
        try {
            $response = $communicator->send($uri, $data);
            $result = $response->getStatusCode();
            $message = $response->getBody();
        } catch (Exception $e) {
            $result = Response::HTTP_INTERNAL_SERVER_ERROR;
            $message = $e->getMessage();
        }
        $this->delayService->finishDelay($basket->getId(), $title);
        $out['result'] = $result;
        $out['message'] = $message;
        $out['data'] = $data;
        $this->logService->create(__METHOD__, 'send_communicator_notify;' . AppHelper::jsonFromArray($out));
    }

    /**
     * @param $promoId
     * @return array|null
     */
    public function getPromo($promoId)
    {
        return null;

        $message = "Req: [promoId={$promoId}] Resp:";
        $url = $_ENV['promo_url'] . '/' . $promoId;
        $options = AppHelper::getCurlOptions('GET', $url);
        try {
            $response = $this->curlWrapper->getQueryResult($options);
            $output = AppHelper::arrayFromJson($response);
            if(isset($output[0]['promoid'])) {
                $this->logService->create(__METHOD__, $message);
                return $output[0];
            } else {
                $this->logService->create(__METHOD__, $message,'ERR0R');
                return null;
            }
        } catch (CurlWrapperException $e) {
            $this->logService->create(__METHOD__, $message . $e->getMessage(), 'ERROR');
            return null;
        }
    }

    /**
     * @param $promo
     * @return mixed
     */
    public function preparePromoNameOutput($promo) {
        return $promo['name'] ?? $_ENV['promo_discountname_cap'];
    }

    /**
     * @param Basket $basket
     * @param $items
     */
    public function updateDiscountName(Basket $basket, $items) {
        foreach ($items as $item) {
            $discounts = $item->getDiscounts();
            if ($discounts != null) {
                $discountMod = [];
                foreach ($discounts as $discount) {
                    $title = 'send-promo';
//                    $this->delayService->initDelay($title);
                    if(isset($discount['discountType'])) {
                        $promo = $this->getPromo($discount['discountType']);
                        $discount['discountType'] = $this->preparePromoNameOutput($promo);
                    } else {
                        $promo = $this->getPromo($discount['discountcode']);
                        $discount['discountname'] = $this->preparePromoNameOutput($promo);
                    }

                    $discountMod[] = $discount;
                }
                $item->setDiscounts($discountMod);
                $this->_persist($item);
            }
        }
    }

    /**
     * @param $items
     */
    public function updateItemActions($items) {
        foreach ($items as $item) {
            $actions = [];
            $discounts = $item->getDiscounts();
            if($discounts != null) {
                foreach ($discounts as $discount) {
                    if(isset($discount['discountType'])) {
                        $actions['discounts'][] = [
                            'code' => $discount['discountType'],
                            'name' => $discount['discountType'],
                        ];
                    } else {
                        $actions['discounts'][] = [
                            'code' => $discount['discountcode'],
                            'name' => $discount['discountname'],
                        ];
                    }
                }
            }
            $item->setActions($actions);
            $this->_persist($item);
        }
    }

    /**
     * @param $items
     * @return array
     */
    public function iterateItems($items)
    {
        $out = [];
        if($items){
            /** @var Item $item */
            foreach ($items as $key=>$item){
                //$item->setDigitalRound();
                $out[$key] = $item->iterateVisible();
            }
        }

        return $out;
    }

    /**
     * @param Order $order
     * @param null $status
     * @return bool
     */
    public function isOrderFinalStatus(Order $order, $status = null)
    {
        if( $isFinalStatus = in_array($order->getStatus(), ShopConst::getMappedStatuses(ShopConst::MAPPING_STATUS_FINAL)) ) {

            foreach(ShopConst::listOverFinalOrderStatusServices() as $service => $statuses) {

                foreach ($statuses as $finalStatus => $overFinalStatus) {

                    if (empty($order->getSourceIdentifier()) ||
                        $order->getSourceIdentifier() == '' &&
                        $service == ShopConst::UR_SAP_ID &&
                        $order->getStatus() == $finalStatus &&
                        $status == $overFinalStatus) {
                        return false;
                    }

                    if ($order->getSourceIdentifier() == $service &&
                        $order->getStatus() == $finalStatus &&
                        $status == $overFinalStatus) {
                        return false;
                    }
                }
            }
        }

        return $isFinalStatus;
    }

    /**
     * @param string $method
     * @param Order $order
     * @param Basket $basket
     * @param Communicator $communicator
     * @param array $params
     * @return int|mixed
     * @throws Exception
     */
    public function overrideLogic(string $method, Order $order, Basket $basket, Communicator $communicator, $params = [])
    {
        $result = Response::HTTP_OK;
        $message = 'Success';
        $historyOrderId = null;
        if($method == 'update') {
            $status = $params['status'];
            $items = $this->repoItem->agregateItemForStatusUpdate($basket);
            $itemsCashboxResponse = empty($basket->getCashboxResponse()) ? $items : ItemHelper::aggrOrderCashboxItemsArray( $this->getItemsFormCashboxResponse($basket) );
            if (ShopConst::isDeliveryTypeRM($order->getDeliveryType())) {
                $doSendToRM = true;
                if (ShopConst::isPaymentStatusRM($status)) {
                    $this->setReManagerUrlPaymentSet();
                    $order->setPaymentInformationDataFromStatus($order, $status);
                    $historyOrderId = $this->insertIntoOrderHistory($order, $basket, $status);
                } else {
                    $this->setReManagerUrlStatusSet();
                    if($status == ShopConst::STATUS_RCS) {
                        if($order->getDeliveryType() == ShopConst::DELIVERY_KEY_TYPE_W12) {
                            $status = ShopConst::STATUS_PRW;
                        }
                    } elseif($status == ShopConst::STATUS_CRE) {
                        $this->setReManagerUrlCreate();
                    } elseif($status == ShopConst::STATUS_RFC) {
                        if($order->getPaymentType() == ShopConst::PAYMENT_KEY_TYPE_O) {
                            if($order->getStatus() == ShopConst::STATUS_ONL) {
                                $doSendToRM = false;
                            }
                        }
                    }
                    $order->setStatus($status);
                    $historyOrderId = $this->insertIntoOrderHistory($order, $basket);
                }
                if ($doSendToRM && $this->checkDestinationToRM($params['destinations'])) {
                    $out = $this->sendReManagerOrder($order, $basket, $itemsCashboxResponse, $communicator, $historyOrderId);
                    $result = $this->notifyIfError($out, $order, $basket, $communicator, 'order-update');
                }
            } else {
                $order->setStatus($status);
                $historyOrderId = $this->insertIntoOrderHistory($order, $basket);
                if(!empty($params['forced'])) {
                    $this->setEshopFields($order, $basket, $items);
                } else {
                    $this->sendEshopOrderData($order, $basket, $items);
                }
            }
            if( $this->checkDestinationToMP($params['destinations']) ) {
                $out = $this->sendToCommunicatorWithDelay($order, $basket, $communicator);
                $result = $this->notifyIfError($out, $order, $basket, $communicator, 'order-update');
            }
        } elseif($method == 'update-rm') {
            $itemsData = ItemHelper::getItemsForOrder($params['itemData']);
            $out = $this->setReManagerUrlStatusSet()->updateReManagerStatus($order, $basket, $itemsData, $params['status'], $communicator);
            if($out['result'] != Response::HTTP_OK) {

                return [
                    'result' => $out['result'],
                    'message' => $out['message'],
                ];
            }
            $historyOrderId = $out['historyOrderId'];
            $result = $this->notifyIfError($out, $order, $basket, $communicator, 'order-update');
            $out = $this->sendToCommunicatorWithDelay($order, $basket, $communicator);
            $result = $this->notifyIfError($out, $order, $basket, $communicator, 'order-update');
        }

        return [
            'result' => $result,
            'message' => $message,
            'historyOrderId' => $historyOrderId,
        ];
    }

    /**
     * @param Order $order
     * @return bool
     */
    protected function isDCOrder(Order $order): bool
    {
        return ($order->getSourceIdentifier() == ShopConst::DC_SAP_ID || $order->getLogagentGln() == ShopConst::DC_SAP_ID);
    }

    /**
     * @param Order $order
     * @return bool
     */
    protected function isSMMOrder(Order $order): bool
    {
        return $order->getSourceIdentifier() == SMMConst::SMM_SAP_ID;
    }

    /**
     * @param Order $order
     * @return bool
     */
    protected function isSbermarketOrder(Order $order): bool
    {
        return $order->getSourceIdentifier() == SberMarketConst::SBERMARKET_SAP_ID;
    }

    /**
     * @param Order $order
     * @return bool
     */
    protected function isUseCasheBox(Order $order): bool
    {
        if( $this->isDCOrder($order) || $this->isSMMOrder($order) || $this->isSbermarketOrder($order)) {
            return false;
        }
        return true;
    }

    /**
     * @param array $out
     * @param Order $order
     * @param Basket $basket
     * @param Communicator $communicator
     * @param string $title
     * @return int|mixed $result
     */
    public function notifyIfError(array $out, Order $order, Basket $basket, Communicator $communicator, string $title)
    {
        $result = isset($out['result']) ? $out['result'] : Response::HTTP_BAD_REQUEST;
        if($result != Response::HTTP_OK) {
            $out = $this->makeOutError($order, $result, $out);
            $this->sendCommunicatorErrorNotify($order, $basket, $communicator, $out);
        }

        return $result;
    }

    /**
     * @param Order $order
     * @param Basket $basket
     * @param Communicator $communicator
     * @return array
     */
    public function sendToCommunicatorWithDelay(Order $order, Basket $basket, Communicator $communicator)
    {
        $title = 'send-communicator';
        $this->delayService->initDelay($title);
        $out = $this->sendToCommunicator($communicator, $order);
        $this->delayService->finishDelay($basket->getId(), $title);

        return $out;
    }

    /**
     * @param $costToDiff
     * @return bool
     */
    protected function tooMuchCostDiff($costToDiff)
    {
        return false;

        $costOrder = $costToDiff['costOrder'];
        $costCashBox = $costToDiff['costCashBox'];

        $now = time();
        $t1 = strtotime('21-01-14');
        $t2 = strtotime('21-01-17');
        $diff = ($now >= $t1 && $now < $t2) ? 17 : 2;

        if (
            $costCashBox
            && $costOrder < $costCashBox
            && 100 * ($costCashBox - $costOrder) / $costCashBox >= $diff
        ) {

            return true;
        } else {

            return false;
        }
    }

    /**
     * @param Order $order
     * @param Basket $basket
     * @return array
     */
    protected function makeEventData(Order $order, Basket $basket): array
    {
        $items = $this->repoItem->isChangedItems($basket) ?
            $this->serializer->toArray($this->repoItem->findBy(['basketId' => $basket->getId()])) :
            $this->cashboxItemMapping($basket);

        return [
            'order'  => $this->serializer->toArray($order),
            'basket' => $this->serializer->toArray($basket),
            'items'  => $items,
        ];
    }

    /**
     * @param Basket $basket
     * @return array
     */
    private function cashboxItemMapping(Basket $basket): array
    {
        $items = [];
        $cashboxResponse = json_decode($basket->getCashboxResponse(), 1);
        if( !empty($cashboxResponse['items']) ) {
            foreach ($cashboxResponse['items'] as $item) {
                $item['cost_one_unit'] = $item['cost'] / $item['quantity'];
                $item['vat_rate'] = $item['vatrate'];
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param string $date
     * @return array
     */
    public function getCouponOnline(string $date = ''): array
    {
        $couponNumber = null;
        $couponTitle = 'Скидка за онлайн оплату';
/*
        $ts = empty($date) ? time() : strtotime($date);
        foreach( $this->couponsOnline as $couponNumberI => $dates) {
            $from = strtotime($dates['from']);
            $to   = strtotime($dates['to']);
            if( $ts >= $from && $ts <= $to ) {
                $couponNumber = $couponNumberI;
                break;
            }
        }
        $dd = date('d', $ts);
        foreach( $this->monthlyCouponsOnline as $couponNumberI => $day) {
            $from = $day['from'];
            $to   = $day['to'];
            if( $dd >= $from && $dd <= $to ) {
                $couponNumber = $couponNumberI;
                break;
            }
        }
*/
        return [
            'number' => $couponNumber,
            'title'  => $couponTitle,
        ];
    }

    /**
     * @param Basket $basket
     * @param $items
     * @return array|null
     */
    protected function getCouponsAppliedResult(Basket $basket, $items): ?array
    {
        $out = null;
        $coupons = $basket->getCoupons();
        if( !empty($coupons) ) {
            foreach ($coupons as $couponNumber => $coupon) {
                $out[] = [
                    'number' => $couponNumber,
                    'applied' => $this->isCouponApplied($couponNumber, $items),
                ];
            }
        }

        return $out;
    }

    /**
     * @param string $couponNumber
     * @param $items
     * @return bool
     */
    private function isCouponApplied(string $couponNumber, $items): bool
    {
        if( !empty($items) ) {
            foreach ($items as $item) {
                $discounts = $item->getDiscounts();
                if( !empty($discounts) ) {
                    foreach ($discounts as $discount) {
                        if (mb_substr_count($discount['campaignname'], $couponNumber) > 0) {

                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    public function getItemsFormCashboxResponse(Basket $basket): array
    {
        $items = [];
        $cashboxResponse = json_decode($basket->getCashboxResponse(), true);
        if( !empty($cashboxResponse['items']) ) {
            foreach ($cashboxResponse['items'] as $crItem) {
                $item = new Item();
                $item->setBarcode($crItem['barcode'] ?? null);
                $item->setArticle($crItem['article'] ?? null);
                $item->setPrice(round($crItem['price'], 2) ?? null);
                $item->setMinPrice(round($crItem['minprice'], 2) ?? null);
                $item->setQuantity($crItem['quantity'] ?? null);
                $item->setName($crItem['name'] ?? null);
                $item->setMeasure($crItem['measure'] ?? null);
                $item->setMeasureName($crItem['measurename'] ?? null);
                $item->setCost(round($crItem['cost'], 2) ?? null);
                $item->setCostOneUnit($crItem['quantity'] ? round($crItem['cost'] / $crItem['quantity'], 3) : null);
                $item->setOldCost(round($crItem['oldcost'], 2) ?? null);
                $item->setpaymentmethod($crItem['paymentmethod'] ?? null);
                $item->setpaymentobject($crItem['paymentobject'] ?? null);
                $item->settaramode($crItem['taramode'] ?? null);
                $item->setvatcode($crItem['vatcode'] ?? null);
                $item->setvatrate($crItem['vatrate'] ?? null);
                $item->setvatsum($crItem['tvatsum'] ?? null);
                $item->setDiscounts($crItem['discounts'] ?? null);
                $item->setEarnedBonuses($crItem['earnedbonuses'] ?? null);
                $item->setExcisemark(!empty($crItem['excisemark']) ? json_decode($crItem['excisemark'], true) : null);
                $items[] = $item;
            }
        }

        return $items;

    }

    public function beginTransaction()
    {
        $this->doctrine->getConnection()->beginTransaction();
    }

    public function commit()
    {
        $this->doctrine->getConnection()->commit();
    }

    public function rollBack()
    {
        $this->doctrine->getConnection()->rollBack();
    }

    private function findCouponByShK($couponShK): ?Coupon
    {
        return $this->em->getRepository(Coupon::class)->findByShK($couponShK);
    }

    private function getCouponTypes(?Coupon $coupon): array
    {
        $return = [];
        if($coupon instanceof Coupon) {
            $couponRestrictions = $coupon->getCouponRestriction();
            foreach($couponRestrictions as $couponRestriction) {
                if ($couponRestriction instanceof CouponRestriction) {
                    $return[] = $couponRestriction->getIdientifikatorRestrictionsCoupons();
                }
            }
        }
        return $return;
    }

    public function getCouponRestrictionTypes(string $couponShK): array
    {
        $return = [];
        if($coupon = $this->findCouponByShK($couponShK)) {
            if($coupon->getStatusAutoname() != ShopConst::COUPON_STATUS_ACTIVE) {
                return [ShopConst::COUPON_TYPE_DISABLED];
            }
            $couponRestrictions = $coupon->getCouponRestriction();
            foreach ($couponRestrictions as $couponRestriction) {
                if ($couponRestriction instanceof CouponRestriction) {
                    $return[] = $couponRestriction->getIdientifikatorRestrictionsCoupons();
                }
            }
        }
        return $return;
    }

    /**
     * @param string $method
     * @param string $url
     * @return array
     */
    private function sendRequest(string $method, string $url): array
    {
        $statusCode = $content = $error = null;
        try {
            $response = $this->client->request($method, $url);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
        } catch (Exception $e ) {
            $error = $e->getMessage();
        }

        return [
            'statusCode' => $statusCode,
            'content' => $content,
            'error' => $error,
        ];
    }

    private function getOrdersFromRM(): array
    {
        $out = [];
        $url = $_ENV['re_manager_url'] . $_ENV['re_manager_method_sync'];
        $response = $this->sendRequest('GET' , $url);
        if($response['statusCode'] != Response::HTTP_OK) {
            echo 'Request error: '.$response['statusCode'].' '.$response['content'];
            exit;
        }
        //$response['content'] = '{"data":{"data1":[{"":"{\"NAKL_LIST\":[{\"NOM_NAKL_EXT\":\"UR-21898-377894\",\"NAME_SOST\":\"REE\",\"LMD_SOST\":\"2021-10-12T10:57:36.040\"}]}"}]}}';
        $content = json_decode($response['content'], 1);
        $list = $content['data']['data1'][0];
        foreach($list as $key => $val) {
            $rmOrders = json_decode($val, 1);
            break;
        }
        foreach($rmOrders['NAKL_LIST'] as $key => $rmOrder) {
            $rmOrderId      = $rmOrder['NOM_NAKL_EXT'] ?? null;
            $rmOrderStatus  = $rmOrder['NAME_SOST'] ?? null;
            $rmOrderUpdated = $rmOrder['LMD_SOST'] ?? null;
            $out[$rmOrderId] = $rmOrderId && $rmOrderStatus ? $rmOrder : null;
        }
        return $out;
    }

    private function compareOrders()
    {
        $diff = [];
        $orderIds = [];
        $rmOrders = $this->getOrdersFromRM();
        foreach($rmOrders as $rmOrderId => $rmOrder) {
            $orderIds[] = $rmOrderId;
        }
        $orders = $this->repoOrder->findOrders($orderIds);
        foreach ($orders as $order) {
            $orderId = $order->getOrderId();
            $orderStatus = $order->getStatus();
            if(
                array_key_exists($orderId, $rmOrders)
                && isset($rmOrders[$orderId]['NAME_SOST'])
                && $orderStatus != $rmOrders[$orderId]['NAME_SOST']
            ) {
                $orderRMStatus = $rmOrders[$orderId]['NAME_SOST'];
                if(!$this->isCompareException($orderStatus, $orderRMStatus)) {
                    $diff[] = [
                        'orderId' => $orderId,
                        'status' => $orderStatus,
                        'statusInfo' => ShopConst::getStatusInfo($orderStatus),
                        'lastUpd' => $order->getUpdated()->format('Y-m-d\TH:i:s'),
                        'rmStatus' => $orderRMStatus,
                        'rmStatusInfo' => ShopConst::getStatusInfo($orderRMStatus),
                        'rmLastUpd' => $rmOrders[$orderId]['LMD_SOST'],
                    ];
                }
            }
        }
        return $diff;
    }

    private function isCompareException(string $statusSZ, string $statusRM): bool
    {
        $exceptions = ShopConst::getOrderStatusDifferentException();

        foreach ($exceptions as $exception) {
            if(array_key_exists($statusSZ, $exception) && $exception[$statusSZ] == $statusRM) {

                return true;
            }
        }

        return false;
    }

    public function sendDiffOrders($mailer)
    {
        $body = '';
        $subject = 'Расхождения по заказам СЗ-РМ';
        if($diff = $this->compareOrders()) {
            foreach ($diff as $str) {
                $body .= "{$str['orderId']} {$str['status']} {$str['lastUpd']} {$str['rmStatus']} {$str['rmLastUpd']}";
            }
            $data['orders'] = $diff;
            $mailer->send($subject, $data);
        }
    }

    /**
     * @param string $markingCode
     * @return array|string|string[]|null
     */
    public function prepareMarkingCode(string $markingCode)
    {
        $markingCode = htmlspecialchars($markingCode);
        return preg_replace('/[^\PC\s]/u', '', $markingCode);
    }

    /**
     * @param array $markingCodes
     * @return array
     */
    public function prepareMarkingCodes(array $markingCodes): array
    {
        $markingCodesPrepared = [];
        foreach ($markingCodes as $markingCode) {
            $markingCodesPrepared[]['label'] = $this->prepareMarkingCode($markingCode['label']);
        }

        return $markingCodesPrepared;
    }

}