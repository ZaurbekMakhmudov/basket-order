<?php

namespace App\BasketOrderBundle\SwgModel\SberMarket;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


class Payload
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("originalOrderId")
     * @SWG\Property(type="integer", description="Партнерский ID заказа")
     */
    public $originalOrderId;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("storeId")
     * @SWG\Property(type="string", description="Номер магазина")
     */
    public $storeId;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("customer")
     * @SWG\Property(type="object", description="Информация о клиенте", ref=@Model(type=App\BasketOrderBundle\SwgModel\SberMarket\Customer::class))
     */
    public $customer;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("delivery")
     * @SWG\Property(type="object", description="Информация о доставке", ref=@Model(type=App\BasketOrderBundle\SwgModel\SberMarket\Delivery::class))
     */
    public $delivery;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("positions")
     * @SWG\Property(type="array", property="positions", description="Данные о лотах", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\SberMarket\Positions::class)))
     */
    public $positions;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("total")
     * @SWG\Property(type="object", description="Итоговые цены", ref=@Model(type=App\BasketOrderBundle\SwgModel\SberMarket\Total::class))
     */
    public $total;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("comment")
     * @SWG\Property(type="string", description="Комментарий")
     */
    public $comment;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("replacementPolicy")
     * @SWG\Property(type="string", description="")
     */
    public $replacementPolicy;


    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("shippingMethod")
     * @SWG\Property(type="string", description="Метод доставки")
     */
    public $shippingMethod;
}