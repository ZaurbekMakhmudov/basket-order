<?php

namespace App\BasketOrderBundle\SwgModel\SberMarket;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


class Positions
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("id")
     * @SWG\Property(type="string", description="ID заказа")
     */
    public $id;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("originalQuantity")
     * @SWG\Property(type="integer", description="Партнерское кол-во товара")
     */
    public $originalQuantity;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("quantity")
     * @SWG\Property(type="integer", description="Кол-во товара")
     */
    public $quantity;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("price")
     * @SWG\Property(type="string", description="Цена товара")
     */
    public $price;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("discountPrice")
     * @SWG\Property(type="string", description="Скидка на один товар")
     */
    public $discountPrice;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("replacedByID")
     * @SWG\Property(type="string", description="Товар замененный данной позицией")
     */
    public $replacedById;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("weight")
     * @SWG\Property(type="string", description="Вес товара")
     */
    public $weight;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("totalPrice")
     * @SWG\Property(type="string", description="Итоговая цена")
     */
    public $totalPrice;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("totalDiscountPrice")
     * @SWG\Property(type="string", description="Итоговая скидка")
     */
    public $totalDiscountPrice;

}