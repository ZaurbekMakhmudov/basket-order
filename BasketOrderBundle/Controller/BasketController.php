<?php

namespace App\BasketOrderBundle\Controller;

use App\BasketOrderBundle\Helper\ItemHelper;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Class BasketController
 * @package App\BasketOrderBundle\Controller
 */
class BasketController extends BaseController
{
    /**
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Parameter(
     *     name="anonim_id",
     *     in="query",
     *     type="string",
     *     required=true,
     *     description="ID анонимного пользователя"
     * )
     * @SWG\Parameter(
     *     name="basket_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="номер корзины"
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
     *      description="получить корзину для анонимного пользователя если нет активной корзины, создать новую",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="HTTP code response"),
     *          @SWG\Property(property="message", type="string", example="info active basket"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class))),
     *          @SWG\Property(property="coupons", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\CouponResult::class)))
     *      )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="anonim ID required"),
     *     )
     * )
     * @Rest\Get("/basket")
     * @SWG\Tag(name="basket")
     * @param $request Request
     * @return Response
     * @throws Exception
     */
    public function info(Request $request)
    {
        $anonimId = $request->get('anonim_id');
        $this->logService->create($request->getRequestUri(), $request);
        $locker = $this->getLocker($this->semaphoreKeyStage::COMMON,  $anonimId);
        if($locker instanceof Response) {
            $this->logService->create($request->getRequestUri(), $locker);
            return $locker;
        }
        $basket = $this->noValidateInfo($request);
        if ($basket instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $basket);
            return $basket;
        }
        $basketId = $request->get('basket_id');
        $storeId = $this->getStoreId($request);
        $this->basketService->initUser($this->getUser());
        $out = $this->basketService->createInfoForBasket($anonimId, $storeId, $basketId);
        $basket = $out['basket'] ?? null;
        $locker->release();
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, 'info'));
        return $this->returnOut($out, $basket, 'info');
    }

    /**
     *
     * @SWG\Parameter(
     *     name="anonim_id",
     *     in="query",
     *     type="string",
     *     required=true,
     *     description="ID анонимного пользователя"
     * )
     *
     * @SWG\Parameter(
     *     name="user_id",
     *     in="query",
     *     type="string",
     *     description="ID авторизованного пользователя"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="получить список корзин для анонимного пользователя",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="baskets", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)))
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="anonim ID required"),
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Get("/baskets")
     * @SWG\Tag(name="basket")
     * @param $request Request
     * @return Response
     * @throws
     */
    public function list(Request $request)
    {
        $this->logService->create($request->getRequestUri(), $request);
        if ($out = $this->noValidateList($request)) {
            $this->logService->create($request->getRequestUri(), $out);
            return $out;
        }
        $anonimId = $request->get('anonim_id');
        $userId = $request->get('user_id');
        $out = $this->basketService->getListBaskets($anonimId, $userId);
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, null, 'list'));
        return $this->returnOut($out, null, 'list');
    }

    /**
     * @SWG\Parameter(
     *     name="basketId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер корзины"
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
     *          required={"item"},
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\itemAdd::class)))
     *       )
     * )
     * @SWG\Parameter(
     *     name="version",
     *     in="query",
     *     type="string",
     *     description="version"
     * )
     * @SWG\Response(
     *      response=200,
     *      description="добавить к корзине позицию товара",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="HTTP code OK response"),
     *          @SWG\Property(property="message", type="string", example="article added into basket "),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class)))
     *      )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket ID required"),
     *          @SWG\Property(property="message1", type="string", example="item Data required"),
     *          @SWG\Property(property="request_body", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\itemAdd::class)))
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket not found")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Post("/basket/{basketId}/add")
     * @SWG\Tag(name="basket")
     * @param $request Request
     * @param $basketId
     * @return Response
     */
    public function add(Request $request, $basketId)
    {
        $locker = $this->initLocker($basketId);
        $this->logService->create($request->getRequestUri(), $request);
        if($locker instanceof Response) {
            $this->logService->create($request->getRequestUri(), $locker);
            return $locker;
        }
        $title = 'add_items';
        $this->delayService->initDelay($title);
        $basket = $this->noValidateAdd($request, $basketId);
        if ($basket instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $basket);
            return $basket;
        }

        $requestBody = json_decode($request->getContent(), true);
        $itemData = ($requestBody and isset($requestBody['items'])) ? $requestBody['items'] : null;
        $this->setBasketStoreId($basket,$request);
        $out = $this->basketService->addItemsToBasket($basket, $itemData);
        $this->basketService->fUpdate($basket);
        $this->delayService->finishDelay($basketId, $title);
        $locker->release();
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, $title));
        return $this->returnOut($out, $basket, $title);
    }

    /**
     * @SWG\Parameter(
     *     name="basketId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер корзины"
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
     *          required={"item"},
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\itemAddItem::class)))
     *       )
     * )
     * @SWG\Parameter(
     *     name="version",
     *     in="query",
     *     type="string",
     *     description="version"
     * )
     * @SWG\Response(
     *      response=200,
     *      description="добавить к корзине позицию товара",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="HTTP code OK response"),
     *          @SWG\Property(property="message", type="string", example="article 123456 added into basket 654321"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class)))
     *      )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket ID required"),
     *          @SWG\Property(property="message1", type="string", example="item Data required")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket not found")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Post("/basket/{basketId}/add-items")
     * @SWG\Tag(name="basket")
     * @param $request Request
     * @param $basketId
     * @return Response
     * @throws
     */
    public function addItems(Request $request, $basketId)
    {
        $title = 'add_items-barcode';
        $this->logService->create($request->getRequestUri(), $request);
        $this->delayService->initDelay($title);
        $basket = $this->noValidateAddItems($request, $basketId);
        if ($basket instanceof Response) {
            $this->logService->create($request->getRequestUri(), $basket);
            return $basket;
        }
        $requestBody = json_decode($request->getContent(), true);
        $itemData = ($requestBody and isset($requestBody['items'])) ? $requestBody['items'] : null;
        $this->setBasketStoreId($basket,$request);
        $out = $this->basketService->addItemsToBasket($basket, $itemData);
        $this->basketService->fUpdate($basket);
        $this->delayService->finishDelay($basketId, $title);
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, $title));
        return $this->returnOut($out, $basket, $title);
    }

    /**
     * @SWG\Parameter(
     *     name="basketId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер корзины"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Parameter(
     *     name = "body",
     *     in ="body",
     *     required=true,
     *     schema = @SWG\Schema(
     *         type="object",
     *         required={"article", "item_qty"},
     *         @SWG\Property(property="article", type="string", description="Артикул товара"),
     *         @SWG\Property(property="item_qty", type="number", description="Количество товаров")
     *     )
     * )
     * @SWG\Parameter(
     *     name="version",
     *     in="query",
     *     type="string",
     *     description="version"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="обновить количество позиции товара",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="HTTP code OK response"),
     *          @SWG\Property(property="message", type="string", example="Qty item updated"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class)))
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket ID required"),
     *          @SWG\Property(property="message1", type="string", example="article not defined"),
     *          @SWG\Property(property="message2", type="string", example="Qty not defined")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket not found"),
     *          @SWG\Property(property="message1", type="string", example="item 123456 not found")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Patch("/basket/{basketId}/updateCounters")
     * @SWG\Tag(name="basket")
     * @param $request Request
     * @param $basketId
     * @return Response
     */
    public function updateCounters(Request $request, $basketId)
    {
        $title = 'update_counters';
        $this->logService->create($request->getRequestUri(), $request);
        $this->delayService->initDelay($title);
        if ($out = $this->noValidateUpdateCounters($request, $basketId)) {
            $this->logService->create($request->getRequestUri(), $out);
            return $out;
        }
        $basket = $this->basketService->findOneBy(['id' => $basketId, 'active' => true]);
        $requestBody = json_decode($request->getContent(), true);
        $article = ItemHelper::getArticleItem($requestBody) ; //($requestBody and isset($requestBody['article'])) ? $requestBody['article'] : null;
        $itemQty = ($requestBody and isset($requestBody['item_qty'])) ? $requestBody['item_qty'] : null;
        $this->setBasketStoreId($basket,$request);
        $out = $this->basketService->updateCounterItemForBasket($basket, $article, $itemQty);
        $this->basketService->fUpdate($basket);
        $this->delayService->finishDelay($basketId, $title);
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, $title));
        return $this->returnOut($out, $basket, $title);
    }

    /**
     * @SWG\Parameter(
     *     name="basketId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер корзины"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Parameter(
     *      name = "coupons",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(
     *          type="object",
     *          required={"coupons"},
     *          @SWG\Property(property="coupons", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\Coupon::class)))
     *       )
     * )
     * @SWG\Parameter(
     *     name="version",
     *     in="query",
     *     type="string",
     *     description="version"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="добавить к корзине купон",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="HTTP code OK response"),
     *          @SWG\Property(property="message", type="string", example="coupon added into basket"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class))),
     *          @SWG\Property(property="coupons", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\CouponResult::class)))
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket ID required")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket not found")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Patch("/basket/{basketId}/coupon")
     * @SWG\Tag(name="basket")
     * @param $request Request
     * @param $basketId
     * @return Response
     * @throws
     */
    public function coupon(Request $request, $basketId)
    {
        $locker = $this->initLocker($basketId);
        $this->logService->create($request->getRequestUri(), $request);
        if($locker instanceof Response) {
            $this->logService->create($request->getRequestUri(), $locker);
            return $locker;
        }
        $title = 'add_coupon';
        $this->delayService->initDelay($title);
        list($basket, $coupons, $couponUser, $couponNotifications) = $this->noValidateCoupon($request, $basketId);
        if ($basket instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $basket);
            return $basket;
        }
        $this->setBasketStoreId($basket,$request);
        $out = $this->basketService->addCouponsToBasket($basket, $coupons);
        $this->basketService->fUpdate($basket);
        $out['couponNotifications'] = $couponNotifications;
        $this->delayService->finishDelay($basketId, $title);
        $locker->release();
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, $title));
        return $this->returnOut($out, $basket, $title);
    }

    /**
     * @SWG\Parameter(
     *     name="basketId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер корзины"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Parameter(
     *      name = "coupons",
     *      in ="body",
     *      required=false,
     *      schema = @SWG\Schema(
     *          type="object",
     *          required={"coupons"},
     *          @SWG\Property(property="coupons", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\Coupon::class)))
     *       )
     * )
     * @SWG\Parameter(
     *     name="version",
     *     in="query",
     *     type="string",
     *     description="version"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="удалить из корзины купон",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="HTTP code OK response"),
     *          @SWG\Property(property="message", type="string", example="coupon deleted from basket"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class))),
     *          @SWG\Property(property="coupons", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\CouponResult::class)))
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket ID required"),
     *          @SWG\Property(property="message1", type="string", example="coupon is null")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket not found")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Delete("/basket/{basketId}/coupon")
     * @SWG\Tag(name="basket")
     * @param $request Request
     * @param $basketId
     * @return Response
     * @throws
     */
    public function couponDelete(Request $request, $basketId)
    {
        $locker = $this->initLocker($basketId);
        $this->logService->create($request->getRequestUri(), $request);
        if($locker instanceof Response) {
            $this->logService->create($request->getRequestUri(), $locker);
            return $locker;
        }
        $title = 'del_coupon';
        $this->delayService->initDelay($title);
        list($basket, $coupons, $couponUser, $couponNotifications) = $this->noValidateCoupon($request, $basketId, true);
        if ($basket instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $basket);
            return $basket;
        }

        $this->setBasketStoreId($basket,$request);
        $out = $this->basketService->delCouponsFromBasket($basket, $coupons);
        $this->basketService->fUpdate($basket);
        $out['couponNotifications'] = $couponNotifications;
        $this->delayService->finishDelay($basketId, $title);
        $locker->release();
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, $title));
        return $this->returnOut($out, $basket, $title);
    }

    /**
     * @SWG\Parameter(
     *     name="basketId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер корзины"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Parameter(
     *     name = "body",
     *     in ="body",
     *     required=true,
     *     schema = @SWG\Schema(
     *         type="object",
     *         required={"card"},
     *         @SWG\Property(property="card", type="string",example="2775076098159", description="Номер карты")
     *     )
     * )
     * @SWG\Parameter(
     *     name="version",
     *     in="query",
     *     type="string",
     *     description="version"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="добавить к корзине карту лояльности",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="HTTP code OK response"),
     *          @SWG\Property(property="message", type="string", example="card added/rename into basket"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class))),
     *          @SWG\Property(property="coupons", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\Coupon::class)))
     *      )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket ID required"),
     *          @SWG\Property(property="message1", type="string", example="card is null")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket not found")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Patch("/basket/{basketId}/card")
     * @SWG\Tag(name="basket")
     * @param $request Request
     * @param $basketId
     * @return Response
     * @throws
     */
    public function card(Request $request, $basketId)
    {
        $locker = $this->initLocker($basketId);
        $this->logService->create($request->getRequestUri(), $request);
        if($locker instanceof Response) {
            $this->logService->create($request->getRequestUri(), $locker);
            return $locker;
        }
        $title = 'add_card';
        $this->delayService->initDelay($title);
        if ($out = $this->noValidateCard($request, $basketId)) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $out);
            return $out;
        }
        $basket = $this->basketService->findOneBy(['id' => $basketId, 'active' => true]);
        $requestBody = json_decode($request->getContent(), true);
        $card = ($requestBody and isset($requestBody['card'])) ? $requestBody['card'] : null;
        $this->setBasketStoreId($basket,$request);
        $out = $this->basketService->setCardToBasket($basket, $card);
        $this->basketService->fUpdate($basket);
        $this->delayService->finishDelay($basketId, $title);
        $locker->release();
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, $title));
        return $this->returnOut($out, $basket, $title);
    }

    /**
     * @SWG\Parameter(
     *     name="basketId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер корзины"
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
     *          required={"payment_type"},
     *          @SWG\Property(property="payment_type", type="string", description="способ оплаты, 1 - онлайн, 0 - наличными, ",example=0)
     *      )
     * )
     * @SWG\Parameter(
     *     name="version",
     *     in="query",
     *     type="string",
     *     description="version"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="добавить к корзине тип оплаты/купон",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="HTTP code OK response"),
     *          @SWG\Property(property="message", type="string", example="paymentType 1 set into basket 123"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class)))
     *      )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket ID required"),
     *          @SWG\Property(property="message1", type="string", example="payment_type is null"),
     *          @SWG\Property(property="request_body", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\RequestPaymentBasket::class)))
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket not found")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Patch("/basket/{basketId}/payment")
     * @SWG\Tag(name="basket")
     * @param $request Request
     * @param $basketId
     * @return Response
     * @throws
     */
    public function payment(Request $request, $basketId)
    {
        $locker = $this->initLocker($basketId);
        $this->logService->create($request->getRequestUri(), $request);
        if($locker instanceof Response) {
            $this->logService->create($request->getRequestUri(), $locker);
            return $locker;
        }
        $title = 'set_payment_type';
        $this->delayService->initDelay($title);
        $basket = $this->noValidatePayment($request, $basketId);
        if ($basket instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $basket);
            return $basket;
        }
        $requestBody = json_decode($request->getContent(), true);
        $paymentType = ($requestBody and isset($requestBody['payment_type'])) ? $requestBody['payment_type'] : null;
        $this->setBasketStoreId($basket,$request);
        $out = $this->basketService->isCouponForPaymentOnLine($basket, $paymentType);
        $this->basketService->fUpdate($basket);
        $this->delayService->finishDelay($basketId, $title);
        $locker->release();
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, 'set payment type'));
        return $this->returnOut($out, $basket, 'set payment type');
    }

    /**
     *
     * @SWG\Parameter(
     *     name="basketId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер корзины"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Parameter(
     *     name="user_id",
     *     in="query",
     *     type="string",
     *     required=true,
     *     description="ID авторизованного пользователя"
     * )
     *
     * @SWG\Parameter(
     *     name="card",
     *     in="query",
     *     type="string",
     *     description="Номер карты"
     * )
     * @SWG\Parameter(
     *     name="version",
     *     in="query",
     *     type="string",
     *     description="version"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="создать из корзины заказ",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="HTTP code OK response"),
     *          @SWG\Property(property="message", type="string", example="basket checkout for order"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class))),
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket ID required"),
     *          @SWG\Property(property="message1", type="string", example="user ID required"),
     *          @SWG\Property(property="message2", type="string", example="userId already exists")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket not found")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Get("/basket/{basketId}/checkout")
     * @SWG\Tag(name="basket")
     * @param $request Request
     * @param $basketId
     * @return Response
     * @throws
     */
    public function checkout(Request $request, $basketId)
    {
        $locker = $this->initLocker($basketId);
        $this->logService->create($request->getRequestUri(), $request);
        if($locker instanceof Response) {
            $this->logService->create($request->getRequestUri(), $locker);
            return $locker;
        }
        $title = 'checkout';
        $this->delayService->initDelay($title);
        $basket = $this->noValidateCheckout($request, $basketId);
        if ($basket instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $basket);
            return $basket;
        }
        $userId = $request->get('user_id');
        $card = $request->get('card');
        $this->setBasketStoreId($basket,$request);
        $out = $this->basketService->checkoutToBasket($basket, $userId, $card);
        $this->basketService->fUpdate($basket);
        $this->delayService->finishDelay($basketId, $title);
        $locker->release();
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, $title));
        return $this->returnOut($out, $basket, $title);
    }

    /**
     * @SWG\Parameter(
     *     name="basketId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер корзины"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Parameter(
     *      name = "body",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(
     *          type="object",
     *          required={"article"},
     *          @SWG\Property(property="article", type="string", description="Артикул товара")
     *      )
     * )
     * @SWG\Parameter(
     *     name="version",
     *     in="query",
     *     type="string",
     *     description="version"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="удалить позицию товара из корзины",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="HTTP code OK response"),
     *          @SWG\Property(property="message", type="string", example="item 123456 from basket removed"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class)))
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket ID required"),
     *          @SWG\Property(property="message1", type="string", example="article not defined")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket not found"),
     *          @SWG\Property(property="message1", type="string", example="item not found")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Patch("/basket/{basketId}/remove")
     * @SWG\Tag(name="basket")
     * @param $request Request
     * @param $basketId
     * @return Response
     */
    public function remove(Request $request, $basketId)
    {
        $title = 'remove_item';
        $this->delayService->initDelay($title);
        $this->logService->create($request->getRequestUri(), $request);
        if ($out = $this->noValidateRemove($request, $basketId)) {
            $this->logService->create($request->getRequestUri(), $out);
            return $out;
        }
        $basket = $this->basketService->findOneBy(['id' => $basketId, 'active' => true]);
        $requestBody = json_decode($request->getContent(), true);
        $article = ($requestBody and isset($requestBody['article'])) ? $requestBody['article'] : null;
        $this->setBasketStoreId($basket,$request);
        $out = $this->basketService->removeFromBasket($basket, $article);
        $this->basketService->fUpdate($basket);
        $this->delayService->finishDelay($basketId, $title);
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, $title));
        return $this->returnOut($out, $basket, $title);
    }

    /**
     *
     * @SWG\Parameter(
     *     name="basketId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер корзины"
     * )
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="удалить все позиции из корзины",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="HTTP code OK response"),
     *          @SWG\Property(property="message", type="string", example="basket 123 cleared"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class))
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="basket ID required")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket not found"),
     *          @SWG\Property(property="message1", type="string", example="items for 123456 not found")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Delete("/basket/{basketId}/clear")
     * @SWG\Tag(name="basket")
     * @param $basketId
     * @return Response
     */
    public function clearBasket($basketId)
    {
        $locker = $this->initLocker($basketId);
        $this->logService->createCustomRequest([
            'method' => 'DELETE',
            'body' => ['basketId' => $basketId],
            'url' => '/basket/' . $basketId . '/clear'
        ]);

        if($locker instanceof Response) {
            $this->logService->create('/basket/' . $basketId . '/clear', $locker);
            return $locker;
        }
        $title = 'clear';
        $this->delayService->initDelay($title);
        $basket = $this->noValidateClearBasket($basketId);
        if ($basket instanceof Response) {
            $locker->release();
            $this->logService->create('/basket/' . $basketId . '/clear', $basket);
            return $basket;
        }
        $out = $this->basketService->clearFromBasket($basket);
        $this->basketService->fUpdate($basket);
        $this->delayService->finishDelay($basketId, $title);
        $locker->release();
        $this->logService->create('/basket/' . $basketId . '/clear', $this->returnOut($out, $basket, $title));
        return $this->returnOut($out, $basket, $title);
    }

    /**
     * @SWG\Parameter(
     *     name="basketId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер корзины"
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
     *     response=200,
     *     description="удалить из корзины карту лояльности",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="HTTP code OK response"),
     *          @SWG\Property(property="message", type="string", example="card added/rename into basket"),
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\BasketDelete::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class)))
     *      )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="basket ID required")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket_id", type="string", example="basket ID "),
     *          @SWG\Property(property="message", type="string", example="basket not found")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @Rest\Delete("/basket/{basketId}/card")
     * @SWG\Tag(name="basket")
     * @param $request Request
     * @param $basketId
     * @return Response
     * @throws
     */
    public function clearCard(Request $request, $basketId)
    {
        $title = 'clear_card';
        $this->delayService->initDelay($title);
        $this->logService->create($request->getRequestUri(), $request);
        if ($out = $this->noValidateClearCard($basketId)) {
            $this->logService->create($request->getRequestUri(), $out);
            return $out;
        }
        $basket = $this->basketService->findOneBy(['id' => $basketId, 'active' => true]);
        $this->setBasketStoreId($basket,$request);
        $out = $this->basketService->clearCardForBasket($basket);
        $this->basketService->fUpdate($basket);
        $this->delayService->finishDelay($basketId, $title);
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, $title));
        return $this->returnOut($out, $basket, $title);
    }
}
