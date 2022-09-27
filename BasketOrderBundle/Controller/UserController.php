<?php

namespace App\BasketOrderBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Class UserController
 * @package App\BasketOrderBundle\Controller
 */
class UserController extends BaseController
{
    /**
     * @SWG\Parameter(
     *     name="userId",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="ID авторизованного пользователя"
     * )
     * @SWG\Response(
     *      response=200,
     *      description="получить список номеров магазинов, в которые были оформлены заказы данного пользователя",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="200"),
     *          @SWG\Property(property="message", type="string", example="get order delivery points"),
     *          @SWG\Property(property="delivery_points", type="array", @SWG\Items(type="string", description="delivery_point_id"))
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
     * @Rest\Get("/user/{userId}/delivery/points")
     * @SWG\Tag(name="user")
     * @param $userId
     * @return Response
     */
    public function getDeliveryPoints($userId)
    {
        $this->logService->create('/user/' . $userId . '/delivery/points', ['userId' => $userId]);
        $result = $this->noValidateUserId($userId);
        if ($result instanceof Response) {
            $this->logService->create('/user/' . $userId . '/delivery/points', $result);
            return $result;
        }
        $out = $this->orderService->getOrderDeliveryPoints($userId);
        $this->logService->create('/user/' . $userId . '/delivery/points', $this->handleView($this->view($out, $out['result'])));
        return $this->handleView($this->view($out, $out['result']));
    }
}