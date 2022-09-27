<?php

namespace App\BasketOrderBundle\Controller;

use App\BasketOrderBundle\Entity\Basket;
use App\BasketOrderBundle\Entity\PartnerItemData;
use App\BasketOrderBundle\Entity\PartnerOrderData;
use App\BasketOrderBundle\Entity\PartnerProperty;
use App\BasketOrderBundle\Repository\PartnerRepository;
use App\CashboxBundle\Entity\Item;
use App\SemaphoreBundle\SemaphoreLocker;
use App\BasketOrderBundle\Entity\OrderHistory;
use App\BasketOrderBundle\Helper\ShopConst;
use App\BasketOrderBundle\Helper\SMMConst;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Security\Core\Security;

class OrderController extends BaseController
{
    /**
     * @SWG\Parameter(
     *     name="number",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер заказа"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Parameter(
     *     name="version",
     *     in="query",
     *     type="string",
     *     description="version"
     * )
     * @SWG\Response(
     *      response=200,
     *      description="получить заказ с указанным номером",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="200"),
     *          @SWG\Property(property="message", type="string", example="message"),
     *          @SWG\Property(property="store_id", type="string", example="800"),
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class))),
     *          @SWG\Property(property="coupons", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\CouponResult::class)))
     *      )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="number order required")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="order not found")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Get("/order/{number}",requirements={})
     * @SWG\Tag(name="order")
     * @param Request $request
     * @param $number
     * @return Response
     */
    public function info(Request $request, $number)
    {
        if(isset($_SERVER['HTTP_DEBUG'])) {
            print_r($_ENV);
            exit;
        }
        $this->logService->create($request->getRequestUri(), $request);
        $order = $this->noValidateOrderInfo($number);
        if ($order instanceof Response) {
            $this->logService->create($request->getRequestUri(), $order);
            return $order;
        }
        $basket = $this->getBasketStoreId($order, $request);
        $out = $this->orderService->getOrderInfo($order, $basket);
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $order, 'info'));
        return $this->returnOut($out, $order, 'info');
    }

    /**
     * @SWG\Parameter(
     *     name="user_id",
     *     in="query",
     *     type="string",
     *     required=true,
     *     description="ID авторизованного пользователя"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="получить список заказов для пользователя",
     *     schema = @SWG\Schema(@SWG\Property(property="orders", type="array", @SWG\Items(
     *                          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)),
     *                          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *                          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class)))
     *                     )
     *            )
     *      )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="user ID required")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Get("/orders")
     * @SWG\Tag(name="order")
     * @param $request Request
     * @return Response
     */
    public function orderList(Request $request)
    {
        $this->logService->create($request->getRequestUri(), $request);
        $userId = $request->get('user_id');
        if (!$userId) {

            return $this->handleView($this->view(['message' => 'user ID required'], Response::HTTP_BAD_REQUEST));
        }
        $orders = $this->orderService->findBy(['userId' => $userId]);
        $orders = $this->orderService->iterateOrderItems($orders);
        $this->logService->create($request->getRequestUri(), $this->handleView($this->view(['orders' => $orders], Response::HTTP_OK)));
        return $this->handleView($this->view(['orders' => $orders], Response::HTTP_OK));
    }

    /**
     * @SWG\Parameter(
     *     name="date_sub_int",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="Интервал дат отправок в относительном формате, например '2 hours ago'.<br> Подробнее о формате: https://www.php.net/manual/ru/datetime.formats.relative.php"
     * )
     * @SWG\Response(
     *      response=200,
     *      description="получить список заказов с ошибками отправок",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="200"),
     *          @SWG\Property(property="message", type="string", example="get order_ids with sends errors"),
     *          @SWG\Property(property="orders", type="array", @SWG\Items(type="string", description="order_id"))
     *      )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Get("/orders/sends/errors")
     * @SWG\Tag(name="order")
     * @param $request Request
     * @return Response
     */
    public function ordersSendsErrors(Request $request): Response
    {
        $this->logService->create($request->getRequestUri(), $request);
        $dateSubInterval = $request->get('date_sub_int') ?? '1 day ago';
        $out = $this->orderService->getOrdersSendsErrors($dateSubInterval);
        $this->logService->create($request->getRequestUri(), $this->handleView($this->view($out, $out['result'])));
        return $this->handleView($this->view($out, $out['result']));
    }

    /**
     * @SWG\Parameter(
     *     name="number",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер заказа"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Parameter(
     *      name = "item",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(
     *          type="object",
     *          required={"payment_type","delivery_type","customer","delivery"},
     *          @SWG\Property(property="payment_type", type="string", description="способ оплаты, 1 - онлайн, 0 - наличными, ",example=1),
     *          @SWG\Property(property="delivery_type", type="string", description="способ доставки - ['E'|1] - курьер; ['W'|2] - самовывоз",example=1),
     *          @SWG\Property(property="customer", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\Customer::class)),
     *          @SWG\Property(property="delivery", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\Delivery::class))
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="добавить к заказу данные о доставке и покупателе и отправить заказ в таблицу синхронизации",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="200"),
     *          @SWG\Property(property="message", type="string", example="order update"),
     *          @SWG\Property(property="store_id", type="string", example="8000"),
     *          @SWG\Property(property="errors", type="string", example="null"),
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class))),
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="number required")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="order not found")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Post("order/{number}/confirm")
     * @SWG\Tag(name="order")
     * @param $request Request
     * @param $number
     * @return Response
     */
    public function confirm(Request $request, $number)
    {
        $locker = $this->initLocker(null, $number);
        $this->logService->create($request->getRequestUri(), $request);
        if($locker instanceof Response) {
            $this->logService->create($request->getRequestUri(), $locker);
            return $locker;
        }
        $title = 'confirm';
        $this->delayService->initDelay($title);
        $order = $this->noValidateOrderConfirm($request, $number);
        if ($order instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $order);
            return $order;
        }
        $basket = $this->getBasketStoreId($order, $request);
        $requestBody = json_decode($request->getContent(), true);
        $this->orderService->parserConfirm($order, $requestBody);
        $this->orderService->fUpdate($order);
        $out = $this->orderService->confirmOrder($request, $order, $basket, $this->getInGatewayCommunicator());
        $this->delayService->finishDelay($basket->getId(), $title);
        $locker->release();
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $order, $title));
        return $this->returnOut($out, $order, $title);
    }

    /**
     * @SWG\Parameter(
     *     name="number",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер заказа"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Parameter(
     *      name = "item",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(
     *          type="object",
     *          required={"payment_type","delivery_type","customer","delivery"},
     *          @SWG\Property(property="payment_type", type="string", description="способ оплаты, 1 - онлайн, 0 - наличными, ",example=0),
     *          @SWG\Property(property="delivery_type", type="string", description="способ доставки - ['E'|1] - курьер; ['W'|2] - самовывоз",example=1),
     *          @SWG\Property(property="delivery_scheme", type="integer", description="схема доставки - 1 - курьер; 2 - доставка УР", example=1),
     *          @SWG\Property(property="customer", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\Customer::class)),
     *          @SWG\Property(property="delivery", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\Delivery::class))
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="добавить в заказ данные о доставке",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="200"),
     *          @SWG\Property(property="message", type="string", example="order update"),
     *          @SWG\Property(property="store_id", type="string", example="8000"),
     *          @SWG\Property(property="errors", type="string", example="null"),
     *          @SWG\Property(property="order_id", type="string", example="UR-20396-15776"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class))),
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="number required")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="order not found")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Patch("order/{number}/delivery")
     * @SWG\Tag(name="order")
     * @param $request Request
     * @param $number
     * @return Response
     * @throws
     */
    public function confirmDelivery(Request $request, $number)
    {
        $locker = $this->initLocker(null, $number);
        $this->logService->create($request->getRequestUri(), $request);
        if($locker instanceof Response) {
            $this->logService->create($request->getRequestUri(), $locker);
            return $locker;
        }
        $title = 'confirm_delivery';
        $this->delayService->initDelay($title);
        $order = $out = $this->noValidateOrderConfirmDelivery($request, $number);
        if ($order instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $order);
            return $order;
        }
        $basket = $this->getBasketStoreId($order, $request);
        $requestBody = json_decode($request->getContent(), true);
        $this->orderService->parserConfirmDelivery($order, $requestBody);
        $this->orderService->fUpdate($order);
        $out = $this->orderService->confirmDeliveryOrder($request, $order, $basket);
        $this->delayService->finishDelay($basket->getId(), $title);
        $locker->release();
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $order, $title));
        return $this->returnOut($out, $order, $title);
    }

    /**
     * @SWG\Parameter(
     *     name="number",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер заказа"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Parameter(
     *      name = "item",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(
     *          type="object",
     *          required={"payment_type","delivery_type","customer"},
     *          @SWG\Property(property="payment_type", type="string", description="способ оплаты, 1 - онлайн, 0 - наличными, ",example=0),
     *          @SWG\Property(property="delivery_type", type="string", description="способ доставки - ['E'|1] - курьер; ['W'|2] - самовывоз",example=1),
     *          @SWG\Property(property="customer", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\Customer::class))
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="добавить к заказу данные о покупателе",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="200"),
     *          @SWG\Property(property="message", type="string", example="order update"),
     *          @SWG\Property(property="store_id", type="string", example="8000"),
     *          @SWG\Property(property="errors", type="string", example="null"),
     *          @SWG\Property(property="order_id", type="string", example="UR-20396-15776"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class))),
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="number required")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="order not found")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Patch("order/{number}/customer")
     * @SWG\Tag(name="order")
     * @param Request $request
     * @param $number
     * @return Response
     */
    public function confirmCustomer(Request $request, $number)
    {
        $locker = $this->initLocker(null, $number);
        $this->logService->create($request->getRequestUri(), $request);
        if($locker instanceof Response) {
            $this->logService->create($request->getRequestUri(), $locker);
            return $locker;
        }
        $title = 'confirm_customer';
        $this->delayService->initDelay($title);
        $order = $this->noValidateOrderConfirmCustomer($request, $number);
        if ($order instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $order);
            return $order;
        }
        $basket = $this->getBasketStoreId($order, $request);
        $requestBody = json_decode($request->getContent(), true);
        $this->orderService->parserConfirmCustomer($order, $requestBody);
        $this->orderService->fUpdate($order);
        $out = $this->orderService->confirmCustomerOrder($request, $order, $basket);
        $this->delayService->finishDelay($basket->getId(), $title);
        $locker->release();
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $order, $title));
        return $this->returnOut($out, $order, $title);
    }

    /**
     * @SWG\Parameter(
     *     name="number",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер заказа"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Parameter(
     *      name = "item",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(
     *          type="object",
     *          required={"payment_type","delivery_type"},
     *          @SWG\Property(property="payment_type", type="string", description="способ оплаты, 1 - онлайн, 0 - наличными, ",example=0)
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="добавить к заказу данные о покупателе",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="200"),
     *          @SWG\Property(property="message", type="string", example="order update"),
     *          @SWG\Property(property="store_id", type="string", example="8000"),
     *          @SWG\Property(property="errors", type="string", example="null"),
     *          @SWG\Property(property="order_id", type="string", example="UR-20396-15776"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class))),
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="number required")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="order not found")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Patch("order/{number}/payment")
     * @SWG\Tag(name="order")
     * @param Request $request
     * @param $number
     * @return Response
     */
    public function confirmPayment(Request $request, $number)
    {
        $locker = $this->initLocker(null, $number);
        $this->logService->create($request->getRequestUri(), $request);
        if($locker instanceof Response) {
            $this->logService->create($request->getRequestUri(), $locker);
            return $locker;
        }
        $title = 'confirm_payment';
        $this->delayService->initDelay($title);
        $order = $this->noValidateOrderConfirmPayment($request, $number);
        if ($order instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $order);
            return $order;
        }
        $basket = $this->getBasketStoreId($order, $request);
        $requestBody = json_decode($request->getContent(), true);
        $this->orderService->parserConfirmPayment($order, $requestBody);
        $this->orderService->fUpdate($order);
        $out = $this->orderService->confirmPaymentOrder($request, $order, $basket);
        $this->delayService->finishDelay($basket->getId(), $title);
        $locker->release();
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $order, $title));
        return $this->returnOut($out, $order, $title);
    }

    /**
     * @SWG\Parameter(
     *     name="number",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер заказа"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Parameter(
     *      name = "item",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(ref=@Model(type=App\BasketOrderBundle\SwgModel\PaymentInformation::class)))
     * )
     * @SWG\Response(
     *     response=200,
     *     description="добавить к заказу данные об оплате",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="200"),
     *          @SWG\Property(property="message", type="string", example="order update"),
     *          @SWG\Property(property="store_id", type="string", example="8000"),
     *          @SWG\Property(property="errors", type="string", example="null"),
     *          @SWG\Property(property="order_id", type="string", example="UR-20396-15776"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class))),
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="number required")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="order not found")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Patch("order/{number}/payment-information")
     * @SWG\Tag(name="order")
     * @param Request $request
     * @param $number
     * @return Response
     */
    public function confirmPaymentInformation(Request $request, $number)
    {
        $locker = $this->initLocker(null, $number);
        $this->logService->create($request->getRequestUri(), $request);
        if($locker instanceof Response) {
            $this->logService->create($request->getRequestUri(), $locker);
            return $locker;
        }
        $title = 'confirm_payment_info';
        $this->delayService->initDelay($title);
        $order = $this->noValidateOrderConfirmPaymentInformation($request, $number);
        if ($order instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $order);
            return $order;
        }
        $basket = $this->getBasketStoreId($order, $request);
        $requestBody = json_decode($request->getContent(), true);
        $this->orderService->parserConfirmPaymentInformation($order, $requestBody);
        $this->orderService->fUpdate($order);
        $out = $this->orderService->confirmPaymentInformationOrder($request, $order, $basket, $this->getInGatewayCommunicator());
        $this->delayService->finishDelay($basket->getId(), $title);
        $locker->release();
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $order, $title));
        return $this->returnOut($out, $order, $title);
    }

    /**
     * @SWG\Parameter(
     *     name="number",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер заказа"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Parameter(
     *      name = "items",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(
     *          type="object",
     *          required={"status"},
     *          @SWG\Property(property="status", type="string", description="Статус"),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\itemAddReManager::class))),
     *          @SWG\Property(property="delivery",  type="object",
     *              @SWG\Property(property="point_date", type="string", format="date", example="YYYY-MM-DD", description="Дата доставки в ПВЗ")
     *          )
     *       )
     * )
     * @SWG\Response(
     *      response=200,
     *      description="обновить заказ, включая статус из МП, и состав заказа из РМ",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="200"),
     *          @SWG\Property(property="message", type="string", example="order update"),
     *          @SWG\Property(property="store_id", type="string", example="8000"),
     *          @SWG\Property(property="order_id", type="string", example="UR-20396-15776"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)),
     *      )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="order not found")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Patch("order/{number}/update")
     * @SWG\Tag(name="order")
     * @param $request Request
     * @param $number
     * @return Response
     * @throws \Exception
     */
    public function updateOrder(Request $request, $number)
    {
        $locker = $this->initLocker(null, $number);
        $this->logService->create($request->getRequestUri(), $request);
        if($locker instanceof Response) {
            $this->logService->create($request->getRequestUri(), $locker);
            return $locker;
        }
        $title = 'order-update';
        $this->delayService->initDelay($title);
        $order = $out = $this->noValidateOrderUpdateOrder($request, $number);
        if ($order instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $order);
            return $order;
        }
        $basket = $this->getBasketStoreId($order, $request);
        $requestBody = json_decode($request->getContent(), true);
        if ($this->security->isGranted('ROLE_MP') || $this->security->isGranted('ROLE_DC') || $this->security->isGranted('ROLE_SMM') || $this->security->isGranted('ROLE_SBERMARKET')) {
            $out = $this->orderService->setOrderStatus($order, $basket, $requestBody, $this->getInGatewayCommunicator());
            $this->orderService->fUpdate($order);
            $return_out = $this->returnOut($out, $order, 'status-update-mp');
        } elseif ($this->security->isGranted('ROLE_RM')) {
            $out = $this->orderService->setOrderStatusRm($order, $basket, $requestBody, $this->getInGatewayCommunicator());
            $this->orderService->fUpdate($order);
            $return_out = $this->returnOut($out, $order, 'status-update-rm');
        } else {
            $return_out = $this->handleView($this->view(['message' => 'access denied'], Response::HTTP_FORBIDDEN));
        }
        $this->delayService->finishDelay($basket->getId(), $title);
        $locker->release();
        if($order->getSourceIdentifier() == SMMConst::SMM_SAP_ID)
            $this->setSMMOptions($order, json_decode($request->getContent(), true));
        $this->logService->create($request->getRequestUri(), $return_out);
        return $return_out;
    }

    /**
     * @SWG\Parameter(
     *     name="number",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер заказа"
     * )
     * @SWG\Response(
     *      response=200,
     *      description="получить историю заказа",
     *     @Model(type=OrderHistory::class)
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="number required")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="order not found")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Get("order/{number}/history")
     * @SWG\Tag(name="order")
     * @param $number
     * @return Response
     * @throws
     */
    public function getOrderHistory($number)
    {
        $this->logService->createCustomRequest([
            'url' => '/order/' . $number . '/history',
            'method' => 'GET',
            'body' => ['number' => $number]
        ]);
        if ($out = $this->noValidateOrderHistory($number)) {
            $this->logService->create('/order/' . $number . '/history', $out);
            return $out;
        }

        $history = $this->getDoctrine()
            ->getRepository(OrderHistory::class)
            ->findByOrderId($number);

        $historyModify = [];
        foreach ($history as $item) {
            $item['status_info'] = ShopConst::getStatusInfo($item['status']);
            $historyModify[] = $item;
        }
        $result = Response::HTTP_OK;
        $out['result'] = $result;
        $out['message'] = 'get order history';
        $out['history'] = $historyModify;
        $this->logService->create('/order/' . $number . '/history', $this->handleView($this->view($out, $result)));
        return $this->handleView($this->view($out, $result));
    }

    /**
     * @SWG\Parameter(
     *      name = "body",
     *      in ="body",
     *      required=true,
     *      @SWG\Schema(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\OrderComplex::class))
     * )
     * @SWG\Parameter(
     *     name="anonim_id",
     *     in="query",
     *     type="string",
     *     required=true,
     *     description="ID анонимного пользователя"
     * )
     * @SWG\Parameter(
     *     name="user_id",
     *     in="query",
     *     type="string",
     *     required=true,
     *     description="ID авторизованного пользователя"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Response(
     *      response=200,
     *      description="создать заказ",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class))
     *      )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="not found")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Tag(name="order")
     * @Rest\Post("/order")
     * @param Request $request
     * @return Response
     */
    public function order(Request $request)
    {
        $anonimId = $request->get('anonim_id');
        $locker = $this->getLocker($this->semaphoreKeyStage::COMMON,  $anonimId);
        $this->logService->create($request->getRequestUri(), $request);
        if($locker instanceof Response) {
            $this->logService->create($request->getRequestUri(), $locker);
            return $locker;
        }
        $response = $this->noValidateOrder($request);
        if ($response instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $response);
            return $response;
        }
        $requestBody = json_decode($request->getContent(), true);
        $isUseCasheBox = $requestBody['isUseCasheBox'] ?? true;
        $orderSourceIdentifier = $requestBody['sourceIdentifier'] ?? null;
        $card = $requestBody['card'] ?? null;
        $partnerData['card_num_partner'] = $requestBody['card_num_partner'] ?? null;
        $partnerData['order_id_partner'] = $requestBody['order_id_partner'] ?? null;
        $partnerData['delivery_cost_sum_partner'] = $requestBody['delivery_cost_sum_partner'] ?? null;

        // basket create
        $title = 'info-basket';
        $this->delayService->initDelay($title);
        $userId = $request->get('user_id');
        $storeId = $this->getStoreId($request);
        $this->basketService->setIsUseCasheBox($isUseCasheBox)->initUser($this->getUser());
        $out = $this->basketService->createInfoForBasket($anonimId, $storeId);
        $basket = $out['basket'] ?? null;
        $this->returnOut($out, $basket, 'info');
        if($out['result'] != Response::HTTP_OK || !$basket) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $this->makeBasketBadReqResp(__METHOD__, __LINE__));
            return $this->makeBasketBadReqResp(__METHOD__, __LINE__);
        }
        $this->delayService->finishDelay($basket->getId(), $title);

        // add items to basket
        $title = 'add_items';
        $itemData = $requestBody['items'];
        $this->delayService->initDelay($title);
        $this->setBasketStoreId($basket, $request);
        if($orderSourceIdentifier == SMMConst::SMM_SAP_ID)
            $issetDiscounts = true;
        else
            $issetDiscounts = false;
        if($requestBody['payment_type'] == ShopConst::PAYMENT_KEY_TYPE_O)
            $sendCashbox = false;
        else
            $sendCashbox = true;
        $out = $this->basketService->addItemsToBasket($basket, $itemData, false, $issetDiscounts);
        $items = $out['items'] ?? null;
        $this->delayService->finishDelay($basket->getId(), $title);
        $this->returnOut($out, $basket, $title);
        if($out['result'] != Response::HTTP_OK || !$items) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $this->makeBasketBadReqResp(__METHOD__, __LINE__, $basket->getId()));
            return $this->makeBasketBadReqResp(__METHOD__, __LINE__, $basket->getId());
        }

        // order create
        $title = 'checkout';
        $this->delayService->initDelay($title);
        $out = $this->basketService->checkoutToBasket($basket, $userId, $card, $partnerData, false, $isUseCasheBox, $orderSourceIdentifier);
        $order = $out['order'] ?? null;
        $this->delayService->finishDelay($basket->getId(), $title);
        $this->returnOut($out, $order, $title);
        if($out['result'] != Response::HTTP_OK || !$order) {
            $locker->release();
            $this->logService->create($request->getRequestUri(),$this->makeBasketBadReqResp(__METHOD__, __LINE__, $basket->getId()));
            return $this->makeBasketBadReqResp(__METHOD__, __LINE__, $basket->getId());
        }

        // order confirm
        $title = 'confirm';
        $this->delayService->initDelay($title);
        $this->orderService->parserConfirm($order, $requestBody);
        $this->orderService->fUpdate($order);
        $out = $this->orderService->confirmOrder($request, $order, $basket, $this->getInGatewayCommunicator());
        $items = $this->basketService->repoItem->findBy(['basketId' => $basket->getId(),]);
        $this->basketService->updateDiscountName($basket, $items);
        $this->basketService->updateItemActions($items);
        $this->delayService->finishDelay($basket->getId(), $title);
        $this->basketService->_flush();
        if($out['result'] != Response::HTTP_OK) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $this->makeBasketBadReqResp(__METHOD__, __LINE__, $order->getId()));
            return $this->makeOrderBadReqResp(__METHOD__, __LINE__, $order->getId());
        }
        $locker->release();
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $order, $title));
        return $this->returnOut($out, $order, $title);
    }

    /**
     *  @SWG\Response(
     *      response=200,
     *      description="получить заказ с указанным номером",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="200"),
     *          @SWG\Property(property="message", type="string", example="message"),
     *          @SWG\Property(property="store_id", type="string", example="800"),
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class))),
     *          @SWG\Property(property="coupons", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\CouponResult::class)))
     *      )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="number order required")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="order not found")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Tag(name="order")
     * @Rest\Get("/order/partner/{partnerSapId}/{partnerOrderId}")
     * @param Request $request
     * @return Response
     */
    public function infoByPartnerOrderId(Request $request, $partnerSapId, $partnerOrderId)
    {
        $order = $this->noValidateOrderInfoByPartner($partnerSapId, $partnerOrderId);
        if ($order instanceof Response) {

            return $order;
        }
        $basket = $this->getBasketStoreId($order, $request);
        $out = $this->orderService->getOrderInfo($order, $basket);

        return $this->returnOut($out, $order, 'info-by-partner-order-id');
    }

    public function setSMMOptions($order, $request)
    {
        $items = $this->SMMService->getPartnerItems($this->entityManager, $order);
        if ($this->SMMService->getMethod($order->getStatus()) != false)
            $method = $this->SMMService->getMethod($order->getStatus());
        else
            return false;
        if ($method === 'partialPacking') {
            $this->SMMService->partialPacking($order, $request, $items, $this->getParameter('smm_token'), $this->entityManager);
            return true;
        }
        $this->SMMService->$method($order, $items, $this->getParameter('smm_token'), $this->entityManager);
    }

}