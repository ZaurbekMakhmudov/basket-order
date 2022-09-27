<?php

namespace App\BasketOrderBundle\SwgModel\DeliveryClub;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/** Стоимость заказа
 * Class Total
 * @package App\BasketOrderBundle\SwgModel\DeliveryClub
 */
class Total
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("totalPrice")
     * @SWG\Property(type="string", description="Стоимость заказа без скидок (без стоимости доставки)")
     */
    public $totalPrice;
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("discountTotalPrice")
     * @SWG\Property(type="string", description="Стоимость заказа со скидкой (без стоимости доставки)")
     */
    public $discountTotalPrice;
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("deliveryPrice")
     * @SWG\Property(type="string", description="Стоимость доставки")
     */
    public $deliveryPrice;
}