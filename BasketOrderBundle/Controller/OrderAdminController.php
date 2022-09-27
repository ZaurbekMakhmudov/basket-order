<?php

namespace App\BasketOrderBundle\Controller;

use App\BasketOrderBundle\Entity\Item;
use App\BasketOrderBundle\Entity\Order;
use App\BasketOrderBundle\Era\EshopOrder;
use App\BasketOrderBundle\Helper\AppHelper;
use App\BasketOrderBundle\Helper\ShopConst;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Class OrderAdminController
 * @Route("/admin")
 * @package App\BasketOrderBundle\Controller
 * @IsGranted("ROLE_ADMIN")
 */
class OrderAdminController extends BaseController
{
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
     *     description="выводить тольк с указанным статусом"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="получить все заказы",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="order", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)))
     *     )
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="access denied, only admin")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Tag(name="admin")
     * @Rest\Get("/orders/all")
     *
     * @param Request $request
     * @return Response
     */
    public function listAll(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 100);
        $status = $request->get('status', null);
        $orders = $this->orderService->findAllBy($page, $limit, $status);

        return $this->handleView($this->view([
            'count' => $this->orderService->repoOrder->countRecords($status),
            'page' => $page,
            'limit' => $limit,
            'intoEshopOrders' => $this->orderService->listAddOrderListToEshopOrders(),
            'orders' => $this->orderService->iterateOrderItems($orders),
        ], Response::HTTP_OK));
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
     *     description="выводить тольк с указанным статусом"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="получить все заказы",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="order", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Order::class)))
     *     )
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="access denied, only admin")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Tag(name="admin")
     * @Rest\Get("/e-orders/dump")
     *
     * @param Request $request
     * @return Response
     */
    public function dumpEshop(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 100);
        $status = $request->get('status', null);
        $out = $this->orderService->findAllByEshop($page, $limit, $status);

        return $this->handleView($this->view($out, Response::HTTP_OK));
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
     *      description="получить данные о заказе из таблицы синхронизации",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="e-order", type="object", ref=@Model(type=App\BasketOrderBundle\Era\EshopOrder::class)),
     *          @SWG\Property(property="positions", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Era\EshopOrderPosition::class)))
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
     *     response=403,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="access denied, only admin")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Tag(name="admin")
     * @Rest\Get("/orderseshop/{number}")
     * @param $number
     * @return Response
     */
    public function infoEshopOrders($number)
    {
        if (!$number) {

            return $this->handleView($this->view(['message' => 'number required'], Response::HTTP_BAD_REQUEST));
        }
        $orders = $this->orderService->repoEshoOrder->findBySentEshopOrders($number);
        $out = [];
        if ($orders) {
            /** @var EshopOrder $order */
            foreach ($orders as $key => $order) {
                $orderId = $order->getOrderId();
                $packedId = $order->getPacketId();
                $items = $this->orderService->repoEshoOrderPosition->findBySentEshopOrderPositions($orderId, $packedId);

                $out[] = [
                    'order' => $order,
                    'items' => [
                        'count' => count($items),
                        'items' => $items,
                    ],];
            }
        }

        return $this->handleView($this->view([
            'count' => count($orders),
            'eshop_orders' => $out,
        ], Response::HTTP_OK));
    }

    /**
     * @SWG\Response(
     *     response=200,
     *     description="получить список статусов заказа",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="states", type="string", example="basket 123456 from cashbox updated")
     *     )
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Tag(name="admin")
     * @Rest\Get("/orders/states")
     *
     * @return Response
     */
    public function statesAll()
    {
        $states = ShopConst::getMappedStatuses(ShopConst::MAPPING_STATUS_ALL);

        return $this->handleView($this->view(['states' => $states], Response::HTTP_OK));
    }

    /**
     * @SWG\Parameter(
     *      name = "properties",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(
     *          type="object",
     *          required={"order_id","status","user_id"},
     *          @SWG\Property(property="order_id", type="string", description="номер заказа",example="UR-71-3c7fb"),
     *          @SWG\Property(property="status", type="string", description="статус заказа",example="CRE"),
     *          @SWG\Property(property="user_id", type="string", description="ИД пользователя",example="f74cdad4-99c8-4fc9-ac04-fef6eebc60e4")
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="удалить из списка заказов выбранные по параметрам заказы",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="orders with parameters remove")
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Пример сообщения с ошибкой",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="свойства для удаления не указаны")
     *     )
     * )
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Tag(name="admin")
     * Rest\Delete("/orders/remove")
     * @param $request Request
     * @return Response
     */
    public function remove(Request $request)
    {
        $requestBody = json_decode($request->getContent(), true);

        $orderId = isset($requestBody['order_id']) ? $requestBody['order_id'] : null;
        $status = isset($requestBody['status']) ? $requestBody['status'] : null;
        $userId = isset($requestBody['user_id']) ? $requestBody['user_id'] : null;

        if (!$orderId and !$status and !$userId) {

            return $this->handleView($this->view(['message' => 'свойства для удаления не указаны'], Response::HTTP_BAD_REQUEST));
        }

        $removes = [];
        $orders = $this->orderService->findAll();
        if ($orders) {
            /** @var Order $order */
            foreach ($orders as $order) {
                $id = $order->getId();
                $oid = $order->getOrderId();
                $st = $order->getStatus();
                $uid = $order->getUserId();
                if ($orderId and $oid == $orderId) {
                    $removes[$id] = $order;
                }
                if ($status and $st == $status) {
                    $removes[$id] = $order;
                }
                if ($userId and $uid == $userId) {
                    $removes[$id] = $order;
                }
            }
        }
        $outs = [];

        if ($removes) {
            /** @var Order $order */
            foreach ($removes as $order) {
                $basket = $this->basketService->findOneBy(['orderId' => $order->getOrderId()]);
                $items = $basket ? $this->itemService->findBy(['basketId' => $basket->getId()]) : null;
                if ($items) {
                    /** @var Item $item */
                    foreach ($items as $item) {
                        $this->itemService->em->remove($item);
                        $outs['item'][] = $item->getArticle();
                    }
                }
                $this->basketService->em->remove($basket);
                $outs['basket'][] = $basket->getId();
                $this->orderService->em->remove($order);
                $outs['order'][] = $order->getOrderId();
            }
            $this->orderService->_flush();
        }

        return $this->handleView($this->view(['removes' => $outs], Response::HTTP_OK));
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
     *     response=200,
     *     description="добавить к заказу данные о покупателе",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="message", type="string", example="order update")
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
     * @Rest\Get("/order/{number}/send-communicator")
     * @SWG\Tag(name="admin")
     * @param Request $request
     * @param $number
     * @return Response
     */
    public function sendCommunicator(Request $request, $number)
    {
        $this->logService->create($request->getRequestUri(), $request);
        $order = $this->noValidateOrderInfo($number);
        if ($order instanceof Response) {
            $this->logService->create($request->getRequestUri(), $order);
            return $order;
        }
        $url = $this->container->getParameter('base_url');
        $out = $this->orderService->sendToCommunicator($this->getInGatewayCommunicator(), $order);
        $out['url'] = $url;
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $order, 'send-communicator'));
        return $this->returnOut($out, $order, 'send-communicator');
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
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\EshopOrder::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\EshopOrderPosition::class)))
     *       )
     * )
     * @SWG\Response(
     *      response=200,
     *      description="обновить заказ, включая статус из МП, и состав заказа из РМ",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class))
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
     * @SWG\Tag(name="admin")
     * @Rest\Patch("/order/{number}/update-from-gw")
     * @param $request Request
     * @param $number
     * @return Response
     */
    public function updateOrder(Request $request, $number)
    {
        $title = 'admin_update-from-gw';
        $this->delayService->initDelay($title);
        $this->logService->create($request->getRequestUri(), $request);
        $order = $this->noValidateOrderUpdateGW($request, $number);
        if ($order instanceof Response) {
            $this->logService->create($request->getRequestUri(), $order);
            return $order;
        }
        $basket = $this->getBasketStoreId($order, $request);
        $requestBody = json_decode($request->getContent(), true);
        $out = $this->orderService->updateOrderStatusGW($order, $basket, $requestBody);
        $result = isset($out['result']) ? $out['result'] : Response::HTTP_BAD_REQUEST;
        if ($result != Response::HTTP_OK) {
            $message = isset($out['message']) ? $out['message'] : 'undefined error on line ' . __LINE__ . ' for  method' .  __METHOD__;
            $out = [
                'result' => $result,
                'message' => $message,
                'order_id' => $order->getOrderId(),
            ];
            $this->logService->create($request->getRequestUri(), $this->returnOut($out, $order, 'order-update'));
            return $this->returnOut($out, $order, 'order-update');
        }
        $items = $out['items'];
        $out = $this->orderService->setOrderStatusGW($order, $basket, $items, $order->getStatus(), $this->getInGatewayCommunicator());
        $this->delayService->finishDelay($basket->getId(), $title);
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $order, 'status-update-mp'));
        return $this->returnOut($out, $order, 'status-update-mp');
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
     *     name="destinations[]",
     *     in="query",
     *     type="array",
     *     required=false,
     *     description="куда отправлять [RM, MP]",
     *     @SWG\Items(
     *         type="string",
     *         example="RM, MP"
     *     )
     * )
     * @SWG\Parameter(
     *      name = "items",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(
     *          type="object",
     *          required={"status"},
     *          @SWG\Property(property="status", type="string", description="Статус"),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\itemAddReManager::class)))
     *       )
     * )
     * @SWG\Response(
     *      response=200,
     *      description="обновить заказ, включая статус из МП, и состав заказа из РМ",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="200"),
     *          @SWG\Property(property="message", type="string", example="order update"),
     *          @SWG\Property(property="order_id", type="string", example="UR-20396-15776"),
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
     * @Rest\Patch("/order/{number}/update")
     * @SWG\Tag(name="admin")
     * @param $request Request
     * @param $number
     * @return Response
     * @throws
     */
    public function updateOrderAdmin(Request $request, $number)
    {
        $title = 'admin-order-update';
        $this->delayService->initDelay($title);
        $this->logService->create($request->getRequestUri(), $request);
        $order = $out = $this->noValidateOrderUpdateOrder($request, $number);
        if ($order instanceof Response) {
            $this->logService->create($request->getRequestUri(), $order);
            return $order;
        }
        $basket = $this->getBasketStoreId($order, $request);
        $requestBody = json_decode($request->getContent(), true);
        $destinations = $request->query->get('destinations') ?? null;
        $out = $this->orderService->setOrderStatus($order, $basket, $requestBody, $this->getInGatewayCommunicator(), $destinations, true);
        $return_out = $this->returnOut($out, $order, $title);
        $this->delayService->finishDelay($basket->getId(), $title);
        $this->logService->create($request->getRequestUri(), $return_out);
        return $return_out;
    }

    /**
     * @SWG\Parameter(
     *     name="store_id",
     *     in="query",
     *     type="string",
     *     required=false,
     *     description="ID Store"
     * )
     * @SWG\Parameter(
     *      name = "orders",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="orders", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\Order::class)))
     *       )
     * )
     * @SWG\Response(
     *      response=200,
     *      description="обновить заказ, включая статус из МП, и состав заказа из РМ",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="order", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Order::class))
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
     * @SWG\Tag(name="admin")
     * @Rest\Patch("/send/gw")
     * @param $request Request
     * @return Response
     * @throws
     */
    public function sendOrderGate(Request $request)
    {
        $this->logService->create($request->getRequestUri(), $request);
        if ($out = $this->noValidateOrderSendGW($request)) {
            $this->logService->create($request->getRequestUri(), $request);
            return $out;
        }
        $requestBody = json_decode($request->getContent(), true);
        $out = [
            'result' => Response::HTTP_OK,
            'message' => 'order request',
            'requestBody' => $requestBody,
        ];
        $this->logService->create(__METHOD__, AppHelper::jsonFromArray($out));
        $out = $this->orderService->sendEshopOrder($requestBody, $this->getInGatewayCommunicator());
        $result = isset($out['result']) ? $out['result'] : Response::HTTP_BAD_REQUEST;
        if ($result != Response::HTTP_OK) {
            $message = isset($out['message']) ? $out['message'] : 'undefined error on line ' . __LINE__ . ' for  method' .  __METHOD__;
            $out = [
                'result' => $result,
                'message' => $message,
            ];
            $this->logService->create($request->getRequestUri(), $this->handleView($this->view($out, $result)));
            return $this->handleView($this->view($out, $result));
        }
        $this->logService->create($request->getRequestUri(), $this->handleView($this->view($out, $result)));
        return $this->handleView($this->view($out, $result));
    }

    /**
     * @SWG\Parameter(
     *      name = "events",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="events", type="array", @SWG\Items(type="string", description="workflowId" )))
     *       )
     * )
     * @SWG\Response(
     *      response=200,
     *      description="отправить ивенты"
     * )
     *
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Tag(name="admin")
     * @Rest\Patch("/send/events")
     * @param $request Request
     * @return Response
     * @throws
     */
    public function sendEvents(Request $request)
    {
        $this->logService->create($request->getRequestUri(), $request);
        $requestBody = json_decode($request->getContent(), true);
        $workflowIds = !empty($requestBody['events']) ? $requestBody['events'] : null;
        if ( empty($workflowIds) ) {

            return $this->makeBadReqResp('events required');
        }
        $out = $this->orderService->sendEvents($workflowIds);
        $this->logService->create($request->getRequestUri(), $this->handleView($this->view($out, $out['result'])));
        return $this->handleView($this->view($out, $out['result']));
    }

}
