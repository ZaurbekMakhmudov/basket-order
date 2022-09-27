<?php

namespace App\BasketOrderBundle\SwgModel\DeliveryClub;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/** Позиция заказа
 * Class Position
 * @package App\BasketOrderBundle\SwgModel\DeliveryClub
 */
class Position
{
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="string", description="Идентификатор продукта во внешней системе")
     */
    public $id;
    /**
     * @Assert\NotBlank
     * @Assert\Positive
     * @SWG\Property(type="integer", description="Количество продуктов")
     */
    public $quantity;
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="string", description="Цена продукта")
     */
    public $price;
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("discountPrice")
     * @SWG\Property(type="string", description="Цена продукта со скидкой")
     */
    public $discountPrice;
}