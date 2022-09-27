<?php

namespace App\BasketOrderBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Class BasketController
 * @Route("/admin")
 * @package App\BasketOrderBundle\Controller
 * @IsGranted("ROLE_ADMIN")
 */
class BasketAdminController extends BaseController
{
    /**
     * @SWG\Parameter(
     *     name="basketId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="номер корзины"
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
     * @SWG\Response(
     *     response=200,
     *     description="добавить к корзине позицию товара",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="article 123456 added into basket 654321")
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="basket ID required"),
     *          @SWG\Property(property="message1", type="string", example="item Data required")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="basket not found")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Tag(name="admin")
     * @Rest\Post("/basket/{basketId}/add")
     * @param $request Request
     * @param $basketId
     * @return Response
     * @throws
     */
    public function add(Request $request, $basketId)
    {
        $title = 'admin_add_items';
        $this->logService->create($request->getRequestUri(), $request);
        $this->delayService->initDelay($title);
        $basket = $this->noValidateAdd($request, $basketId);
        if ($basket instanceof Response) {
            $this->logService->create($request->getRequestUri(), $basket);
            return $basket;
        }
        $requestBody = json_decode($request->getContent(), true);
        $itemData = ($requestBody and isset($requestBody['items'])) ? $requestBody['items'] : null;
        $out = $this->basketService->addItemsToBasket($basket, $itemData);
        $message = $out['message'] ?? 'undefined error on line ' . __LINE__ . ' for  method' . __METHOD__;
        $result = $out['result'] ?? Response::HTTP_BAD_REQUEST;
        $this->delayService->finishDelay($basketId, $title);
        $this->logService->create($request->getRequestUri(),$this->handleView($this->view($message, $result)));
        return $this->handleView($this->view($message, $result));
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
     * @SWG\Response(
     *     response=200,
     *     description="обновить количество позиции товара",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="Qty item 123456 updated")
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="basket ID required"),
     *          @SWG\Property(property="message1", type="string", example="article not defined"),
     *          @SWG\Property(property="message2", type="string", example="Qty not defined")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="basket not found"),
     *          @SWG\Property(property="message1", type="string", example="item 123456 not found")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Tag(name="admin")
     * @Rest\Patch("/basket/{basketId}/updateCounters")
     * @param $request Request
     * @param $basketId
     * @return Response
     */
    public function updateCounters(Request $request, $basketId)
    {
        $title = 'admin_update_counters';
        $this->logService->create($request->getRequestUri(), $request);
        $this->delayService->initDelay($title);
        if ($out = $this->noValidateUpdateCounters($request, $basketId)) {
            $this->logService->create($request->getRequestUri(), $out);
            return $out;
        }
        $basket = $this->basketService->findOneBy(['id' => $basketId]);
        $requestBody = json_decode($request->getContent(), true);
        $article = ($requestBody and isset($requestBody['article'])) ? $requestBody['article'] : null;
        $item = $basket ? $this->itemService->findOneBy(['article' => $article, 'basketId' => $basket->getId()]) : null;
        $itemQty = ($requestBody and isset($requestBody['item_qty'])) ? $requestBody['item_qty'] : null;
        $out = $this->basketService->updateCounterItemForBasket($basket, $item, $itemQty);
        $this->delayService->finishDelay($basketId, $title);
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, 'update_counters'));
        return $this->returnOut($out, $basket, 'update_counters');
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
     *      name = "coupons",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(
     *          type="object",
     *          required={"coupons"},
     *          @SWG\Property(property="coupons", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\Coupon::class)))
     *       )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="добавить к корзине купон",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class))
     *      )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="basket ID required"),
     *          @SWG\Property(property="message1", type="string", example="coupon is null")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="basket not found")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Tag(name="admin")
     * @Rest\Patch("/basket/{basketId}/coupon")
     * @param $request Request
     * @param $basketId
     * @return Response
     * @throws
     */
    public function coupon(Request $request, $basketId)
    {
        $title = 'admin_add_coupon';
        $this->logService->create($request->getRequestUri(), $request);
        $this->delayService->initDelay($title);
        list($basket, $coupons, $couponUser, $couponNotifications) = $this->noValidateCoupon($request, $basketId);
        if ($basket instanceof Response) {
            $this->logService->create($request->getRequestUri(), $basket);
            return $basket;
        }
        $out = $this->basketService->addCouponsToBasket($basket, $coupons);
        $this->delayService->finishDelay($basketId, $title);
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, 'add_coupon'));
        return $this->returnOut($out, $basket, 'add_coupon');
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
     *     name = "body",
     *     in ="body",
     *     required=true,
     *     schema = @SWG\Schema(
     *         type="object",
     *         required={"card"},
     *         @SWG\Property(property="card", type="string",example="2775076098159", description="Номер карты")
     *     )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="добавить к корзине карту лояльности",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class))
     *      )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="basket ID required"),
     *          @SWG\Property(property="message1", type="string", example="card is null")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="basket not found")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Tag(name="admin")
     * @Rest\Patch("/basket/{basketId}/card")
     * @param $request Request
     * @param $basketId
     * @return Response
     * @throws
     */
    public function card(Request $request, $basketId)
    {
        $title = 'admin_add_card';
        $this->logService->create($request->getRequestUri(), $request);
        $this->delayService->initDelay($title);
        if ($out = $this->noValidateCard($request, $basketId)) {
            $this->logService->create($request->getRequestUri(), $out);
            return $out;
        }
        $basket = $this->basketService->findOneBy(['id' => $basketId]);
        $requestBody = json_decode($request->getContent(), true);
        $card = ($requestBody and isset($requestBody['card'])) ? $requestBody['card'] : null;
        $out = $this->basketService->setCardToBasket($basket, $card);
        $this->delayService->finishDelay($basketId, $title);
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, 'add_card'));
        return $this->returnOut($out, $basket, 'add_card');
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
     *      name = "body",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(
     *          type="object",
     *          required={"article"},
     *          @SWG\Property(property="article", type="string", description="Артикул товара")
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="удалить позицию товара из корзины",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="item 123456 removed")
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="basket ID required"),
     *          @SWG\Property(property="message1", type="string", example="article not defined")
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="basket not found"),
     *          @SWG\Property(property="message1", type="string", example="item not found")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Tag(name="admin")
     * @Rest\Patch("/basket/{basketId}/remove")
     * @param $request Request
     * @param $basketId
     * @return Response
     */
    public function remove(Request $request, $basketId)
    {
        $title = 'admin_remove_item';
        $this->logService->create($request->getRequestUri(), $request);
        $this->delayService->initDelay($title);
        if ($out = $this->noValidateRemove($request, $basketId)) {
            $this->logService->create($request->getRequestUri(), $out);
            return $out;
        }
        $basket = $this->basketService->findOneBy(['id' => $basketId]);
        $requestBody = json_decode($request->getContent(), true);
        $article = ($requestBody and isset($requestBody['article'])) ? $requestBody['article'] : null;
        $item = $basket ? $this->itemService->findOneBy(['article' => $article, 'basketId' => $basket->getId()]) : null;
        $out = $this->basketService->removeFromBasket($basket, $item);
        $this->delayService->finishDelay($basketId, $title);
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, 'remove_item'));
        return $this->returnOut($out, $basket, 'remove_item');
    }

    /**
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
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
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="basket ID required"),
     *     )
     * )
     * @SWG\Response(
     *      response=200,
     *      description="получить корзину для анонимного пользователя если нет активной корзины, создать новую",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class)))
     *      )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="basket not found")
     *     )
     * )
     *
     * @Rest\Get("/basket/{basketId}")
     * @SWG\Tag(name="admin")
     * @param Request $request
     * @param $basketId
     * @return Response
     */
    public function infoBasket(Request $request, $basketId)
    {
        $title = 'admin_info';
        $this->logService->create($request->getRequestUri(), $request);
        $this->delayService->initDelay($title);
        if (!$basketId) {
            $this->logService->create($request->getRequestUri(), $this->handleView($this->view(['message' => 'basket ID required'], Response::HTTP_BAD_REQUEST)));
            return $this->handleView($this->view(['message' => 'basket ID required'], Response::HTTP_BAD_REQUEST));
        }
        $this->basketService->initUser($this->getUser());
        $basket = $this->basketService->findOneBy(['id' => $basketId]);
        if (!$basket) {
            $this->logService->create($request->getRequestUri(), $this->handleView($this->view(['message' => 'basket not found'], Response::HTTP_NOT_FOUND)));
            return $this->handleView($this->view(['message' => 'basket not found'], Response::HTTP_NOT_FOUND));
        }
        $items = $this->itemService->findBy(['basketId' => $basketId]);
        $storeId = $this->getStoreId($request);
        $out = [
            'result' => Response::HTTP_OK,
            'message' => 'info basket',
            'storeId' => $storeId,
        ];
        isset($basket) ? $out['basket'] = $basket : null;
        isset($items) ? $out['items'] = $items : null;
        $this->delayService->finishDelay($basketId, $title);
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, 'info'));
        return $this->returnOut($out, $basket, 'info');
    }

    /**
     * @SWG\Parameter(
     *     name="page",
     *     in="query",
     *     type="string",
     *     description="номер страницы"
     * )
     * @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     type="string",
     *     description="позиций на странице"
     * )
     * @SWG\Parameter(
     *     name="status",
     *     in="query",
     *     type="string",
     *     description="true - активные, false - неактивные"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="вывести все корзины",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="baskets", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Basket::class)))
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Tag(name="admin")
     * @Rest\Get("/baskets/all")
     * @param Request $request
     * @return Response
     */
    public function allList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 100);
        $status = $request->get('status');
        $this->logService->create($request->getRequestUri(), $request);
        $baskets = $this->basketService->allList($page, $limit, $status);
        $out = [
            'count' => ($status !== null and in_array($status, [true, false, '0', '1'])) ?
                $this->basketService->repoBasket->countRecords(['active' => $status]) : $this->basketService->repoBasket->countRecords(),
            'page' => $page,
            'limit' => $limit,
            'baskets' => $baskets,
        ];
        $this->logService->create($request->getRequestUri(), $this->handleView($this->view($out, Response::HTTP_OK)));
        return $this->handleView($this->view($out, Response::HTTP_OK));
    }
}
