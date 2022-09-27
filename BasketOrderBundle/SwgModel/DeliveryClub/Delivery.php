<?php


namespace App\BasketOrderBundle\SwgModel\DeliveryClub;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/** Информация по доставке
 * Class Delivery
 * @package App\BasketOrderBundle\SwgModel\DeliveryClub
 */
class Delivery
{
    /**
     * @Assert\NotBlank
     * @Assert\DateTime(format="Y-m-d\TH:i:sP")
     * @JMS\SerializedName("expectedDateTime")
     * @SWG\Property(type="string", description="")
     */
    public $expectedDateTime;
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\Address::class))
     */
    public $address;
    /**
     * @JMS\SerializedName("slotId")
     * @SWG\Property(type="string", description="Идентификатор слота доставки")
     */
    public $slotId;
}