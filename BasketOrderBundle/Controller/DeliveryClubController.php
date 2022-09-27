<?php

namespace App\BasketOrderBundle\Controller;

use App\BasketOrderBundle\Helper\ShopConst;
use App\BasketOrderBundle\Service\TokenService;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Class DeliveryClubController
 * @package App\BasketOrderBundle\Controller
 */
class DeliveryClubController extends BaseController
{
    /** Создание токена
     * @SWG\Response(
     *      response=200,
     *      description="Токен",
     *      @SWG\Schema(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\Token::class))
     * )
     * @SWG\Response(response=400, description="Ошибка запроса")
     * @SWG\Response(response=401, description="Не авторизован")
     * @SWG\Response(response=500, description="Ошибка сервиса")
     * @SWG\Tag(name="dc")
     * @Rest\Post("/authentication/token")
     * @param Request $request
     * @param TokenService $tokenService
     * @return Response
     */
    public function getDCToken(Request $request, TokenService $tokenService) : Response {
        $this->logService->create($request->getRequestUri(), $request);
        if( $tokenData = $tokenService->createToken($this->security->getUser()->getUsername(), 86400) ) {
            $resultCode = Response::HTTP_OK;
            $out = $tokenData['data'];
        } else {
            $resultCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $out = '';
        }
        $this->logService->createCustomResponse([
            'code' => Response::HTTP_OK,
            'content' => $out
        ]);

        return $this->handleView($this->view($out, $resultCode));
    }

    /** Создание заказа
     * @SWG\Parameter(
     *     name="storeId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="Идентификатор склада"
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\CreateOrder::class))
     * )
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", description="Bearer <token>")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Информация по заказу",
     *     @SWG\Schema(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\OrderStatus::class))
     * )
     * @SWG\Response(response=400, description="Ошибка запроса")
     * @SWG\Response(response=401, description="Не авторизован")
     * @SWG\Response(response=404, description="Склад не найден")
     * @SWG\Response(response=500, description="Ошибка сервиса")
     * @SWG\Tag(name="dc")
     * @Rest\Post("/stores/{storeId}/orders")
     * @param Request $requestFromDC
     * @param $storeId
     * @return Response
     */
    public function createDCOrder(Request $requestFromDC, $storeId) : Response
    {
        $basketId = 0;
        $title = 'dc-create-order';
        $this->logService->create($requestFromDC->getRequestUri(), $requestFromDC);
        $this->delayService->initDelay($title);
        $response = $this->createOrderValidate($requestFromDC);
        if ($response instanceof Response) {
            $this->logService->create($requestFromDC->getRequestUri(), $response);
            return $response;
        }
        $bodyToDC = null;
        $bodyToSZ = $this->deliveryClubService->createOrderRequestBodyDCtoSZ($requestFromDC, $storeId);
        $requestFromDCBody = json_decode($requestFromDC->getContent(), true);
        $bodyToSZ['sourceIdentifier'] = ShopConst::DC_SAP_ID;
        $anonimId = $userId = $requestFromDCBody['customer']['phone'];
        $requestToSZ = $this->deliveryClubService->prepareRequestToSZ($bodyToSZ,
            ['anonim_id' => $anonimId, 'user_id' => $userId]
        );
        $this->logService->create(ShopConst::ORDER_METHODS['order'], $requestToSZ, null, true, true);
        $responseFromSZ = $this->forward(ShopConst::ORDER_METHODS['order'],
            ['request' =>  $requestToSZ],
            ['store_id' => $storeId]
        );
        $this->logService->create(ShopConst::ORDER_METHODS['order'], $responseFromSZ, null, null, true);
        $statusCodeFromSZ = $responseFromSZ->getStatusCode();
        if($statusCodeFromSZ == Response::HTTP_OK) {
            $contentFromSZ = json_decode($responseFromSZ->getContent(), 1);
            $basketId = $contentFromSZ['basket']['id'];
            $bodyToDC = $this->deliveryClubService->createOrderResponseBodySZToDC($responseFromSZ);
            if( $this->isDCOrderCreatedStatusSuccess($responseFromSZ) ) {
                if(!$this->getParameter('dc_confirm_enabled')) {
                    $responseFromSZ = $this->confirmDCOrder($requestFromDC, $storeId, $bodyToDC);
                    $statusCodeFromSZ = $responseFromSZ->getStatusCode();
                    $bodyToDC = json_decode($responseFromSZ->getContent(), 1);
                }
            }
        } else {
            $this->logService->create($requestFromDC->getRequestUri(), $responseFromSZ);

        }
        $basketId ? $this->delayService->finishDelay($basketId, $title) : null;
        $this->logService->create($requestFromDC->getRequestUri(), $responseFromSZ);
        return $this->handleView($this->view($bodyToDC, $statusCodeFromSZ));
    }


    /** Подтверждение заказа
     * @SWG\Parameter(
     *     name="storeId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="Идентификатор склада"
     * )
     * @SWG\Parameter(
     *    name="orderId",
     *    in="path",
     *    type="string",
     *    required=true,
     *    description="Номер заказа"
     * )
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", description="Bearer <token>")
     * @SWG\Response(response=500, description="Ошибка сервиса")
     * @SWG\Response(
     *     response=200,
     *     description="Информация по заказу",
     *     @SWG\Schema(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\OrderStatus::class))
     * )
     * @SWG\Response(response=400, description="Ошибка запроса")
     * @SWG\Response(response=401, description="Не авторизован")
     * @SWG\Tag(name="dc")
     * @Rest\Put ("/stores/{storeId}/orders/{orderId}/confirmation")
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", description="Bearer <token>")
     * @param Request $requestFromDC
     * @param $storeId
     * @param $orderId
     * @return Response
     */
    public function confirmDCOrder(Request $requestFromDC, $storeId, $orderId): Response
    {
        if($this->getParameter('dc_confirm_enabled')) {
            $title = 'dc-confirm-order';
            $basketId = 0;
            $this->logService->create($requestFromDC->getRequestUri(), $requestFromDC);
        }
        $order = $this->orderService->findOneBy(['orderId' => $orderId]);
        if(!$order)
            return new JsonResponse([
                'message' => 'Заказ не найден'
            ], 400);
        $this->logService->create(
            ShopConst::DC_METHODS['setDCStatus'],
            $this->deliveryClubService->prepareRequestToSZ(['status' => 'accepted']),
            null,
            true,
            true
        );
        $responseFromSZ = $this->forward(ShopConst::DC_METHODS['setDCStatus'], [
            'requestFromDC' => $this->deliveryClubService->prepareRequestToSZ(['status' => 'accepted'], [
                'storeId' => $storeId,
                'orderId' => $order->getOrderId()
            ]),
            'storeId' => $storeId,
            'orderId' => $order->getOrderId()
        ]);
        $this->logService->create(
            ShopConst::DC_METHODS['setDCStatus'],
            $responseFromSZ,
            null,
            null,
            true
        );
        if($responseFromSZ->getStatusCode() == Response::HTTP_OK) {
            if($this->getParameter('dc_confirm_enabled')) {
                $basketId ? $this->delayService->finishDelay($basketId, $title) : null;
                return new JsonResponse([
                    'order_id' => $order->getOrderId()
                ]);
            }
            return $responseFromSZ;
        }
        return new JsonResponse([
            'message' => 'Undefined exception',
            'status' => $responseFromSZ->getStatusCode()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @param $responseFromSZ
     * @return bool
     */
    private function isDCOrderCreatedStatusSuccess($responseFromSZ)
    {
        $responseFromSZBody = json_decode($responseFromSZ->getContent(), true);
        $status = $responseFromSZBody['order']['status'];

        return $status == ShopConst::STATUS_CRE || $status == ShopConst::STATUS_ONL;

    }

    /** Получение информации по заказу
     * @SWG\Parameter(
     *     name="storeId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="Идентификатор склада"
     * )
     * @SWG\Parameter(
     *     name="orderId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="Идентификатор заказа во внешней системе"
     * )
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", description="Bearer <token>")
     *
     * @SWG\Response(
     *      response=200,
     *      description="Информация по заказу",
     *      schema = @SWG\Schema(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\OrderInfo::class))
     * )
     * @SWG\Response(response=400, description="Ошибка запроса")
     * @SWG\Response(response=401, description="Не авторизован")
     * @SWG\Response(response=404, description="Заказ не найден")
     * @SWG\Response(response=500, description="Ошибка сервиса")
     * @Rest\Get("/stores/{storeId}/orders/{orderId}")
     * @SWG\Tag(name="dc")
     * @param Request $requestFromDC
     * @param $storeId
     * @param $orderId
     * @return Response
     */
    public function getDCOrder(Request $requestFromDC, $storeId, $orderId) : Response
    {
        $basketId = 0;
        $this->logService->create($requestFromDC->getRequestUri(), $requestFromDC);
        $response = $this->getOrderValidate($requestFromDC);
        if ($response instanceof Response) {

            return $response;
        }
        $bodyToDC = null;
        $this->logService->create(ShopConst::ORDER_METHODS['info'], $requestFromDC, null, true, true);
        $responseFromSZ = $this->forward(ShopConst::ORDER_METHODS['info'],
            ['request' => $requestFromDC, 'number' => $orderId],
            ['store_id' => $storeId]
        );
        $this->logService->create(ShopConst::ORDER_METHODS['info'], $responseFromSZ, null, null, true);
        $statusCodeFromSZ = $responseFromSZ->getStatusCode();
        if($statusCodeFromSZ == Response::HTTP_OK) {
            $contentFromSZ = json_decode($responseFromSZ->getContent(), 1);
            $basketId = $contentFromSZ['basket']['id'];
            $bodyToDC = $this->deliveryClubService->getOrderResponseBodySZToDC($responseFromSZ);
            $this->logService->create($requestFromDC->getRequestUri(), $bodyToDC);
        } else {
            if($statusCodeFromSZ == Response::HTTP_CONFLICT) {
                $statusCodeFromSZ = Response::HTTP_BAD_REQUEST;
            }
            $this->logService->create($requestFromDC->getRequestUri(), $responseFromSZ);
        }

        return $this->handleView($this->view($bodyToDC, $statusCodeFromSZ));

    }

    /** Изменение статуса заказа
     * @SWG\Parameter(
     *     name="storeId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="Идентификатор склада"
     * )
     * @SWG\Parameter(
     *     name="orderId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="Идентификатор заказа во внешней системе"
     * )
     * @SWG\Parameter(
     *      name = "body",
     *      in ="body",
     *      required=true,
     *      @SWG\Schema(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\Status::class))
     * )
     * @SWG\Parameter(name="Authorization", in="header", required=true, type="string", description="Bearer <token>")
     *
     * @SWG\Response(
     *      response=200,
     *      description="Информация по заказу",
     *      @SWG\Schema(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\OrderStatus::class))
     * )
     * @SWG\Response(response=400, description="Ошибка запроса")
     * @SWG\Response(response=401, description="Не авторизован")
     * @SWG\Response(response=404, description="Склад/заказ не найден")
     * @SWG\Response(response=500, description="Ошибка сервиса")
     * @Rest\Put("/stores/{storeId}/orders/{orderId}/status")
     * @SWG\Tag(name="dc")
     * @param Request $requestFromDC
     * @param $storeId
     * @param $orderId
     * @return Response
     */
    public function setDCStatus(Request $requestFromDC, $storeId, $orderId) : Response
    {
        $basketId = 0;
        $title = 'dc-set-status';
        $this->delayService->initDelay($title);
        $this->logService->create($requestFromDC->getRequestUri(), $requestFromDC);
        $response = $this->setStatusValidate($requestFromDC);
        if ($response instanceof Response) {

            return $response;
        }
        $requestBody = json_decode($requestFromDC->getContent(), true);
        if($requestBody['status'] == 'delivered') {
            // call auto-payed (так как при создании все заказы от ДС приходят уже оплаченными)
            $this->logService->create(ShopConst::ORDER_METHODS['confirmPaymentInformation'], $this->deliveryClubService->prepareRequestToSZ(['status' => 1]), null, true, true);
            $responseFromSZ = $this->forward(ShopConst::ORDER_METHODS['confirmPaymentInformation'],
                ['request' => $this->deliveryClubService->prepareRequestToSZ(['status' => 1]), 'number' => $orderId],
                ['store_id' => $storeId]
            );
            $this->logService->create(ShopConst::ORDER_METHODS['confirmPaymentInformation'], $responseFromSZ, null, null, true);
        }

        $bodyToDC = null;
        $bodyToSZ = $this->deliveryClubService->setStatusRequestBodyDCToSZ($requestFromDC);
        $this->logService->create(ShopConst::ORDER_METHODS['updateOrder'], $this->deliveryClubService->prepareRequestToSZ($bodyToSZ), null, true, true);
        $responseFromSZ = $this->forward(ShopConst::ORDER_METHODS['updateOrder'],
            ['request' => $this->deliveryClubService->prepareRequestToSZ($bodyToSZ), 'number' => $orderId],
            ['store_id' => $storeId]
        );
        $this->logService->create(ShopConst::ORDER_METHODS['updateOrder'], $responseFromSZ, null, null, true);
        $statusCodeFromSZ = $responseFromSZ->getStatusCode();
        if($statusCodeFromSZ == Response::HTTP_OK) {
            $contentFromSZ = json_decode($responseFromSZ->getContent(), 1);
            $basketId = $contentFromSZ['basket']['id'];
            $bodyToDC = $this->deliveryClubService->setStatusResponseBodySZToDC($responseFromSZ);
            $this->logService->create($requestFromDC->getRequestUri(), $responseFromSZ);
        } else {
            if($statusCodeFromSZ == Response::HTTP_CONFLICT) {
                $statusCodeFromSZ = Response::HTTP_BAD_REQUEST;
            }
            $this->logService->create($requestFromDC->getRequestUri(), $responseFromSZ);
        }
        $basketId ? $this->delayService->finishDelay($basketId, $title) : null;

        return $this->handleView($this->view($bodyToDC, $statusCodeFromSZ));
    }
}