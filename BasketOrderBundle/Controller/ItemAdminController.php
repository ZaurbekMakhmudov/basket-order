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
class ItemAdminController extends BaseController
{
    /**
     * @SWG\Parameter( name="X-RAINBOW-ESHOP-KEY", in="header", required=false, type="string", description="Authorization" )
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", description="Bearer <token>" )
     * @SWG\Parameter(
     *     name="version",
     *     in="query",
     *     type="string",
     *     description="version"
     * )
     * @SWG\Parameter(
     *      name = "item",
     *      in ="body",
     *      required=true,
     *      schema = @SWG\Schema(
     *          type="object",
     *          required={"item"},
     *          @SWG\Property(property="item", type="string", description="список продуктов, через  ,",example=12244)
     *      )
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
     *      description="получить список продуктов",
     *      schema = @SWG\Schema(
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class)))
     *      )
     * )
     * @Rest\Post("/items")
     * @SWG\Tag(name="admin")
     * @return Response
     */
    public function infoItems(Request $request)
    {
        $this->logService->create($request->getRequestUri(), $request);
        $requestBody = json_decode($request->getContent(), true);
        $item = ($requestBody and isset($requestBody['item'])) ? $requestBody['item'] : null;
        $this->itemService->initUser($this->getUser());
        $item = $this->itemService->findOneBy(['article' => $item]);
        if (!$item) {
            $this->logService->create($request->getRequestUri(), $this->handleView($this->view(['message' => 'items not found'], Response::HTTP_NOT_FOUND)));
            return $this->handleView($this->view(['message' => 'items not found'], Response::HTTP_NOT_FOUND));
        }
        $out = [
            'result' => Response::HTTP_OK,
            'message' => 'info item',
            'item' => $item,
        ];
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, null, 'info item'));
        return $this->returnOut($out, null, 'info item');
    }
}
