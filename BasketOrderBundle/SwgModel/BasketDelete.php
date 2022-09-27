<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 30.07.19
 * Time: 10:11
 */

namespace App\BasketOrderBundle\SwgModel;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Class App\BasketOrderBundle\SwgModel\Basket
 * @package App\BasketOrderBundle\SwgModel
 */
class BasketDelete
{
    /**
     * @SWG\Property(type="number", format="integer")
     */
    private $id;

    /**
     * @SWG\Property(type="string", description="ИД авторизованного пользовотеля приложения")
     */
    private $userId;

    /**
     * @SWG\Property(type="string", description="ИД пользователя")
     */
    private $anonimId;

    /**
     * @SWG\Property(type="string", description="ИД заказа")
     */
    private $orderId;

    /**
     * @SWG\Property(type="number", format="float", example="", description="стоимость корзины до скидки")
     */
    private $price;
    /**
     * @SWG\Property(type="number", format="float", example="", description="стоимость корзины со скидками")
     */
    private $cost;

    /**
     * @SWG\Property(type="string", description="код валюты")
     */
    private $currency;

    /**
     * @SWG\Property(type="string", format="date-time", example="", description="дата создания")
     */
    private $created;

    /**
     * @SWG\Property(type="string", format="date-time", example="", description="дата обновления")
     */
    private $updated;

    /**
     * @SWG\Property(type="number", format="float", example="", description="масса товаров")
     */
    private $weight;

    /**
     * @SWG\Property(type="boolean", description="статус корзины, активная - рабочая, неактивная-нерабочая")
     */
    private $active;

    /**
     * @ORM\Column(name="identifier",type="string", length=50, nullable=true, options={"comment" = "" })
     * @SWG\Property(type="string", example="", description="внутренний идентификатор для обмена с вирт кассой")
     */
    private $identifier;

    /**
     * @SWG\Property(type="integer", example="", description="накопленные балы")
     */
    private $pointsForEarn;
    /**
     * @SWG\Property(type="number", format="float", example="", description="Стоимость доставки")
     */
    private $pointsForSpend;
    /**
     * @SWG\Property(type="string", example="", description="")
     */
    private $signature;
    /**
     * @SWG\Property(type="number", format="float", example="", description="объем всех товаров в корзине")
     */
    private $volume;
    /**
     * @SWG\Property(type="number", format="int32", example="", description="")
     */
    private $itemsCount;
    /**
     * @SWG\Property(property="coupons", type="object", description="купоны к корзине", ref=@Model(type=App\BasketOrderBundle\SwgModel\Coupons::class))
     */
    private $coupons;
    /**
     * @SWG\Property(type="string", example="", description="номер карты")
     */
    private $cardNum;
    /**
     * @SWG\Property(property="card", type="object", description="данные по картте из вирт кассы")
     */
    private $card;
}