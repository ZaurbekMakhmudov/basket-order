<?php

namespace App\BasketOrderBundle\Controller;

use App\BasketOrderBundle\Entity\Basket;
use App\BasketOrderBundle\Entity\Item;
use App\BasketOrderBundle\Entity\Order;
use App\BasketOrderBundle\Entity\PartnerItemData;
use App\BasketOrderBundle\Entity\PartnerOrderData;
use App\BasketOrderBundle\Entity\PartnerProperty;
use App\BasketOrderBundle\Helper\ShopConst;
use App\BasketOrderBundle\Helper\SMMConst;
use App\BasketOrderBundle\Repository\OrderRepository;
use App\BasketOrderBundle\Service\SMMService;
use App\BasketOrderBundle\Service\TokenService;
use Doctrine\DBAL\Driver\AbstractOracleDriver\EasyConnectString;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints\JsonValidator;
use Symfony\Component\Validator\Constraint;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;



class SMMController extends BaseController
{

    public Constraint $constraint;
    private ?object $order;

    public function __contruct(Constraint $constraint, OrderRepository $orderRepository)
    {
        $this->constraint = $constraint;
        $this->orderRepository = $orderRepository;
    }


    /**
     * Добавление заказа
     * @SWG\Response(
     *     response=200,
     *     description="Успешный ответ",
     *     @SWG\Schema(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\Success::class))
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\OrderNew::class))
     * )
     * @SWG\Response(response=400, description="Ошибка запроса")
     * @SWG\Response(response=401, description="Не авторизован")
     * @SWG\Response(response=500, description="Ошибка сервиса")
     * @SWG\Tag(name="SMM")
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", description="Basic <login:password>" )
     * СММ -> api.basket-order
     * @Route("/smm/order/new", methods={"POST"})
     * @param Request $request
     */
    public function new(Request $request): Response
    {
        $requestBody = json_decode($request->getContent(), true);
        $this->logService->create($request->getRequestUri(), $request);
        $title = 'smm-create-order';
        foreach ($requestBody['data']['shipments'] as $shipment) {
            /*$this->delayService->initDelay($title);*/
            if(!$this->SMMService->validateOrderNewProperties($shipment)){
                $this->logService->create($request->getRequestUri(), ['message' => 'Заказ не прошел валидацию']);
                $this->logService->create($request->getRequestUri(), $this->SMMService->getErrorResponse());
                return $this->SMMService->getErrorResponse();
            }
            if(!$this->SMMService->isPrepaid($shipment)) {
                $this->logService->create($request->getRequestUri(), ['message' => 'Заказ не был предоплачен']);
                $this->logService->create($request->getRequestUri(), $this->SMMService->getErrorResponse());
                return $this->SMMService->getErrorResponse();
            }
            $shipmentToSZ = $shipment;
            $shipmentToSZ['items'] = $this->SMMService->prepareItemsToSZ($shipment);
            $requestToSZ = $this->SMMService->createOrderRequestBodySMMtoSZ($shipmentToSZ);
            $requestToSZ['sourceIdentifier'] = SMMConst::SMM_SAP_ID;
            $anonimId = $userId = $shipment['customer']['phone'];
            if(!$this->SMMService->sumIsValid($shipment['items'])){
                $this->logService->create($request->getRequestUri(), ['message' => 'Сумма заказа не валидна']);
                $this->logService->create($request->getRequestUri(), $this->SMMService->getErrorResponse());
                return $this->SMMService->getErrorResponse();
            }
            $requestToSZ = $this->SMMService->prepareRequestToSZ($requestToSZ,
                ['anonim_id' => $anonimId, 'user_id' => $userId]
            );
            $this->logService->create(ShopConst::ORDER_METHODS['order'], $requestToSZ, null, null, true);
            $responseFromSZ = $this->forward(ShopConst::ORDER_METHODS['order'],
                ['request' => $requestToSZ]
            );
            $this->logService->create(ShopConst::ORDER_METHODS['order'], $responseFromSZ, null, null, true);
            if($responseFromSZ->getStatusCode() == Response::HTTP_OK) {
                $this->order = $this->orderService->findOneBy(['orderIdPartner' => $shipment['handover']['deliveryId']]);
                $bodyToSZ = $this->SMMService->getAcceptStatus();
                $this->logService->create(ShopConst::ORDER_METHODS['updateOrder'], $this->SMMService->prepareRequestToSZ($bodyToSZ), null, null, true);
                $responseFromSZ = $this->forward(ShopConst::ORDER_METHODS['updateOrder'],
                    ['request' => $this->SMMService->prepareRequestToSZ($bodyToSZ), 'number' => $this->order->getOrderId()],
                    ['store_id' => $shipment['handover']['outletId']]
                );
                $this->logService->create(ShopConst::ORDER_METHODS['updateOrder'], $responseFromSZ, null, null, true);
                $partnerData = $this->SMMService->getPartnerData($requestBody);
                $this->SMMService->setPartnerData($partnerData, $this->entityManager, $this->order);
            } else {
                $this->logService->create($request->getRequestUri(), $this->SMMService->getErrorResponse());
                return $this->SMMService->getErrorResponse();
            }
            /*$basketId ? $this->delayService->finishDelay($basketId, $title) : null;*/
        }
        $this->logService->create($request->getRequestUri(), $this->SMMService->getSuccessResponse());
        return $this->SMMService->getSuccessResponse();
    }
    /**
     * Отмена заказа
     * @SWG\Response(
     *     response=200,
     *     description="Успешный ответ",
     *     @SWG\Schema(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\Success::class))
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\OrderCancel::class))
     * )
     * @SWG\Response(response=400, description="Ошибка запроса")
     * @SWG\Response(response=401, description="Не авторизован")
     * @SWG\Response(response=500, description="Ошибка сервиса")
     * @SWG\Tag(name="SMM")
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", description="Basic <login:password>" )
     * @Route("/smm/order/cancel", methods={"POST"})
     * @param Request $request
     */
    public function cancel(Request $request)
    {
        /*$title = 'smm-set-canceled';*/
        /*$this->delayService->initDelay($title);*/
        $requestBody = json_decode($request->getContent(), true);
        $this->logService->create($request->getRequestUri(), $request);
        foreach ($requestBody['data']['shipments'] as $shipment)
        {
            if(!$this->SMMService->validateOrderCancelProperties($shipment)){
                $this->logService->create($request->getRequestUri(), ['message' => 'Заказ не прошел валидацию']);
                return $this->SMMService->getErrorResponse();
            }
            $deliveryId = $this->SMMService->getDeliveryIdByShipmentId($shipment['shipmentId'], $this->entityManager);
            if(!$deliveryId){
                $this->logService->create($request->getRequestUri(), ['message' => 'Заказ не найден']);
                return $this->SMMService->getErrorResponse();
            }
            $order = $this->orderService->findOneBy(['orderIdPartner' => $deliveryId]);
            if(!$order){
                $this->logService->create($request->getRequestUri(), ['message' => 'Заказ не найден']);
                return $this->SMMService->getErrorResponse();
            }
            $bodyToSMM = null;
            $bodyToSZ = $this->SMMService->setCancelStatus();
            $this->logService->create(ShopConst::ORDER_METHODS['updateOrder'], $this->SMMService->prepareRequestToSZ($bodyToSZ), null, true, true);
            $responseFromSZ = $this->forward(ShopConst::ORDER_METHODS['updateOrder'],
                ['request' => $this->SMMService->prepareRequestToSZ($bodyToSZ), 'number' => $order->getOrderId()],
                ['store_id' => 1]
            );
            $this->logService->create(ShopConst::ORDER_METHODS['updateOrder'], $responseFromSZ, null, null, true);
            $statusCodeFromSZ = $responseFromSZ->getStatusCode();
            if($statusCodeFromSZ == Response::HTTP_OK) {
                $contentFromSZ = json_decode($responseFromSZ->getContent(), 1);
                /*$this->SMMService->cancelResult($order, $itemsData, $this->getParameter('smm_token'));*/
                try {
                    $basketId = $contentFromSZ['basket']['id'];
                } catch (\Exception $e) {
                    $basket = $this->basketService->findOneBy(['orderId' => $order->getOrderId()]);
                    $basketId = $basket->getId();
                }
                $bodyToSMM = $this->SMMService->setStatusResponseBodySZToSMM($responseFromSZ);
            } else {
                if($statusCodeFromSZ == Response::HTTP_CONFLICT) {
                    $statusCodeFromSZ = Response::HTTP_BAD_REQUEST;
                }
            }
            /*$basketId ? $this->delayService->finishDelay($basketId, $title) : null;*/
        }
        $this->logService->create($request->getRequestUri(), $this->SMMService->getSuccessResponse());
        return $this->SMMService->getSuccessResponse();
    }


    /**
     * @Route("/smm/order/get/items", methods={"POST"})
     */
    public function items(Request $request)
    {
        $requestBody = json_decode($request->getContent(), true);
        $order = $this->orderService->findOneBy(['orderIdPartner' => $requestBody['orderIdPartner']]);
        if(!$order)
            return new JsonResponse("Заказ не найден");
        $basket = $this->basketService->findOneBy(['orderId' => $order->getOrderId()]);
        $items = $this->itemService->findBy(['basketId' => $basket->getId()]);
        $itemsData = [];
        foreach($items as $item) {
            $itemsData[] = [
                "name"=>$item->getName(),
                "price"=>$item->getPrice(),
                "cost"=>$item->getCost(),
                "cost_one_unit"=>$item->getCostOneUnit(),
                "original_quantity"=>$item->getOriginalQuantity(),
                "quantity" => $item->getQuantity()
            ];
        }
        return new JsonResponse($itemsData);
    }

}
