<?php

namespace App\BasketOrderBundle\Controller;

use App\BasketOrderBundle\Helper\SberMarketConst;
use App\BasketOrderBundle\Helper\ShopConst;
use App\BasketOrderBundle\Service\BaseService;
use App\BasketOrderBundle\Service\SberMarketService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraint;
use App\BasketOrderBundle\Repository\OrderRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class SberMarketController extends BaseController
{

    public Constraint $constraint;
    private ?object $order;

    public function __contruct(Constraint $constraint, OrderRepository $orderRepository)
    {
        $this->constraint = $constraint;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Получение ивента от Сбермаркета
     * @SWG\Response(
     *     response=200,
     *     description="Успешный ответ",
     *     @SWG\Schema(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\SberMarket\Success::class))
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\SberMarket\GetWebhook::class))
     * )
     * @SWG\Response(response=400, description="Ошибка запроса")
     * @SWG\Response(response=401, description="Не авторизован")
     * @SWG\Response(response=500, description="Ошибка сервиса")
     * @SWG\Tag(name="SberMarket")
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", description="Basic <login:password>" )
     * @Route("/sbermarket/event", methods={"POST"})
     */
    public function getWebhook(Request $request): Response
    {
        $this->logService->create($request->getRequestUri(), $request);
        $requestBody = json_decode($request->getContent(), true);
        $event = $this->sberMarketService->getEventType($requestBody);
        if (!$event) {
            $this->logService->create($request->getRequestUri(), $this->sberMarketService->getError('ENF'));
            return $this->sberMarketService->getError('ENF');
        }
        $method = $event['method'];
        $responseSuccess = $this->sberMarketService->getResponse($method, $requestBody['payload']['originalOrderId']);
        if ($method == 'created') {
            $this->logService->create($request->getRequestUri(), $responseSuccess);
            return $responseSuccess;
        }
        if ($method == 'assembled') {
            $requestToSZ = $this->sberMarketService->createOrderRequestBodySberMarketToSZ($requestBody);
            $requestToSZ['sourceIdentifier'] = SberMarketConst::SBERMARKET_SAP_ID;
            if(isset($requestBody['payload']['customer'])) {
                $anonimId = $userId = $requestBody['payload']['customer']['phone'];
            } else {
                $anonimId = $userId = '89000000000';
            }
            $requestToSZ = $this->sberMarketService->prepareRequestToSZ($requestToSZ,
                ['anonim_id' => $anonimId, 'user_id' => $userId]
            );
            $this->logService->create(ShopConst::ORDER_METHODS['order'], $requestToSZ, null, null, true);
            $responseFromSZ = $this->forward(ShopConst::ORDER_METHODS['order'],
                ['request' => $requestToSZ]
            );
            $this->logService->create(ShopConst::ORDER_METHODS['order'], $responseFromSZ, null, null, true);
            if ($responseFromSZ->getStatusCode() == Response::HTTP_OK) {
                $this->order = $this->orderService->findOneBy(['orderIdPartner' => $requestBody['payload']['originalOrderId'], 'sourceIdentifier' => SberMarketConst::SBERMARKET_SAP_ID]);
                if (!$this->order){
                    $this->logService->create($request->getRequestUri(), $this->sberMarketService->getError('ONF'));
                    return $this->sberMarketService->getError('ONF');
                }
                $bodyToSZ = $this->sberMarketService->getAcceptStatus();
                $requestToSZ = $this->sberMarketService->prepareRequestToSZ($bodyToSZ);
                $this->logService->create(ShopConst::ORDER_METHODS['updateOrder'], $requestToSZ, null, null, true);
                $responseFromSZ = $this->forward(ShopConst::ORDER_METHODS['updateOrder'],
                    ['request' => $requestToSZ, 'number' => $this->order->getOrderId()],
                    ['store_id' => $requestBody['payload']['store_id']]
                );
                $this->logService->create(ShopConst::ORDER_METHODS['updateOrder'], $responseFromSZ, null, null, true);
                $partnerData = $this->sberMarketService->getPartnerData($requestBody);
                $this->sberMarketService->setPartnerData($partnerData, $this->entityManager, $this->order);
                $this->logService->create($request->getRequestUri(), $responseSuccess);
                return $responseSuccess;
            }
            $this->logService->create($request->getRequestUri(), $this->sberMarketService->getError('EF'));
            return $this->sberMarketService->getError('EF');
        }
        if ($method == 'delivered' || $method == 'canceled') {
            $this->order = $this->orderService->findOneBy(['orderIdPartner' => $requestBody['payload']['originalOrderId'], 'sourceIdentifier' => SberMarketConst::SBERMARKET_SAP_ID]);
            if (!$this->order){
                $this->logService->create($request->getRequestUri(), $this->sberMarketService->getError('ONF'));
                return $this->sberMarketService->getError('ONF');
            }
            if ($method == 'delivered')
                $bodyToSZ = $this->sberMarketService->getDeliveredStatus();
            else
                $bodyToSZ = $this->sberMarketService->getCancelStatus();

            $requestToSZ = $this->sberMarketService->prepareRequestToSZ($bodyToSZ);
            $this->logService->create(ShopConst::ORDER_METHODS['updateOrder'], $requestToSZ, null, null, true);
            $responseFromSZ = $this->forward('App\BasketOrderBundle\Controller\OrderController::updateOrder',
                ['request' => $requestToSZ, 'number' => $this->order->getOrderId()],
                ['store_id' => $requestBody['payload']['store_id']]
            );
            $this->logService->create(ShopConst::ORDER_METHODS['updateOrder'], $responseFromSZ, null, null, true);
            if ($responseFromSZ->getStatusCode() == Response::HTTP_OK) {
                $this->logService->create($request->getRequestUri(), $responseSuccess);
                return $responseSuccess;
            }
            $this->logService->create($request->getRequestUri(), $this->sberMarketService->getError('EF'));
            return $this->sberMarketService->getError('EF');
        }
        $this->logService->create($request->getRequestUri(), $this->sberMarketService->getError('ENF'));
        return $this->sberMarketService->getError('ENF');
    }

    /**
     * @Route("/sbermarket/{path}", requirements={"path"=".*"})
     */
    public function notFound(Request $request): Response
    {
        $this->logService->create($request->getRequestUri(), $request);
        $this->logService->create($request->getRequestUri(), $this->sberMarketService->getError('RNF'));
        return new Response(null, Response::HTTP_NOT_FOUND);
    }
}