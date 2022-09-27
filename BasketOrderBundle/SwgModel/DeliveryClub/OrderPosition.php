<?php

namespace App\BasketOrderBundle\SwgModel\DeliveryClub;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class OrderPosition
 * @package App\BasketOrderBundle\SwgModel\DeliveryClub
 */
class OrderPosition
{
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="string", description="Идентификатор продукта")
     */
    private $id;
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("originalQuantity")
     * @SWG\Property(type="integer", description="Кол-во исходно заказанное")
     */
    private $originalQuantity;
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="string", description="Кол-во в заказе текущее")
     */
    private $quantity;
    /**
     * @JMS\SerializedName("formedQuantity")
     * @SWG\Property(type="string", description="Кол-во скомплектованное. Может быть дробным, если товар весовой.")
     */
    private $formedQuantity;
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="string", description="Цена продукта без скидки")
     */
    private $price;
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("discountPrice")
     * @SWG\Property(type="string", description="Цена продукта со скидкой")
     */
    private $discountPrice;
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("totalPrice")
     * @SWG\Property(type="string", description="Стоимость позиции без скидок")
     */
    private $totalPrice;
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("discountTotalPrice")
     * @SWG\Property(type="string", description="Стоимость позиции со скидкой")
     */
    private $discountTotalPrice;
    /**
     * @JMS\SerializedName("updatedDateTime")
     * @SWG\Property(ref="#/definitions/DateTimeDef")
     */
    private $updatedDateTime;

}