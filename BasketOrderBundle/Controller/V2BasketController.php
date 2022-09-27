<?php

namespace App\BasketOrderBundle\Controller;

use App\BasketOrderBundle\Helper\ItemHelper;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class V2BasketController
 * @Route("/v2")
 * @package App\BasketOrderBundle\Controller
 */
class V2BasketController extends BaseController
{
    /*
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
     *          @SWG\Property(property="basket", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\Basket::class)),
     *          @SWG\Property(property="items", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\Entity\Item::class)))
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
     * @SWG\Tag(name="v2-basket")
     * @param $request Request
     * @return Response
     * @throws Exception
     *
    public function info(Request $request)
    {
        $basket = $this->noValidateInfo($request);
        if ($basket instanceof Response) {

            return $basket;
        }
        $anonimId = $request->get('anonim_id');
        $locker = $this->getLocker($this->semaphoreKeyStage::COMMON,  $anonimId);
        if($locker instanceof Response) {

            return $locker;
        }
        $storeId = $this->getStoreId($request);
        $this->basketService->initUser($this->getUser());
        $out = $this->basketService->createInfoForBasketV2($anonimId, $storeId, $basket);
        $locker->release();

        return $this->returnOut($out, $out['basket'], 'info_v2');
    }
*/

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
     *          required={"coupon"},
     *          @SWG\Property(property="coupon", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\Coupon::class))
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
     *     description="добавить к корзине пользовательский купон",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="HTTP code OK response"),
     *          @SWG\Property(property="message", type="string", example="coupon added into basket"),
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
     * @Rest\Patch("/basket/{basketId}/coupon")
     * @SWG\Tag(name="v2-basket")
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
        $title = 'add_coupon_v2';
        $this->delayService->initDelay($title);
        list($basket, $coupons, $couponUser, $couponNotifications) = $this->noValidateCoupon($request, $basketId);
        if ($basket instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $basket);
            return $basket;
        }
        $this->setBasketStoreId($basket,$request);
        $out = $this->basketService->addCouponUserToBasket($basket, $couponUser);
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
     *     name="version",
     *     in="query",
     *     type="string",
     *     description="version"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="удалить из корзины пользовательский купон",
     *     schema = @SWG\Schema(
     *          @SWG\Property(property="result", type="string", example="HTTP code OK response"),
     *          @SWG\Property(property="message", type="string", example="coupon deleted from basket"),
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
     * @SWG\Tag(name="v2-basket")
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
        $title = 'del_coupon_v2';
        $this->delayService->initDelay($title);
        list($basket, $coupons, $couponUser, $couponNotifications) = $this->noValidateCoupon($request, $basketId, true);
        if ($basket instanceof Response) {
            $locker->release();
            $this->logService->create($request->getRequestUri(), $basket);
            return $basket;
        }
        $this->setBasketStoreId($basket,$request);
        $out = $this->basketService->delCouponUserFromBasket($basket);
        $this->basketService->fUpdate($basket);
        $out['couponNotifications'] = $couponNotifications;
        $this->delayService->finishDelay($basketId, $title);
        $locker->release();
        $this->logService->create($request->getRequestUri(), $this->returnOut($out, $basket, $title));
        return $this->returnOut($out, $basket, $title);
    }


}