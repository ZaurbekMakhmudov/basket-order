<?php

namespace App\BasketOrderBundle\SwgModel\SMM;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


class Items
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("itemIndex")
     * @SWG\Property(type="string", description="Порядковый номер лота")
     */
    public $itemIndex;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("goodsId")
     * @SWG\Property(type="string", description="Идентификатор карточки товара СберМегаМаркет")
     */
    public $goodsId;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("offerId")
     * @SWG\Property(type="string", description="Идентификатор оффера продавца")
     */
    public $offerId;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("itemName")
     * @SWG\Property(type="string", description="Наименование товара")
     */
    public $itemName;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("price")
     * @SWG\Property(type="integer", description="Цена")
     */
    public $price;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("finalPrice")
     * @SWG\Property(type="integer", description="Цена с учетом скидок")
     */
    public $finalPrice;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("discounts")
     * @SWG\Property(type="array", property="discounts", description="Описание скидок",  @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\Discounts::class)))
     */
    public $discounts;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("quantity")
     * @SWG\Property(type="integer", description="Количество")
     */
    public $quantity;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("taxRate")
     * @SWG\Property(type="string", description="Налоговая ставка")
     */
    public $taxRate;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("reservationPerformed")
     * @SWG\Property(type="boolean", description="Внутренний параметр резервации товара в системе СберМегаМаркет")
     */
    public $reservationPerformed;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("isDigitalMarkRequired")
     * @SWG\Property(type="boolean", description="Для некоторых категорий товаров
    https://xn--80ajghhoc2aj1c8b.xn--p1ai/")
     */
    public $isDigitalMarkRequired;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("handover")
     * @SWG\Property(type="object", description="Данные о дате выдачи и магазине выдачи", ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\Handover::class))
     */
    public $handover;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("customer")
     * @SWG\Property(type="object", description="Данные о покупателe", ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\Customer::class))
     */
    public $customer;
}