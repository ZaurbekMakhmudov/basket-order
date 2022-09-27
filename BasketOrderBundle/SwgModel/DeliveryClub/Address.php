<?php
namespace App\BasketOrderBundle\SwgModel\DeliveryClub;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/** Адрес клиента
 * Class Address
 * @package App\BasketOrderBundle\SwgModel\DeliveryClub
 */
class Address
{
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\City::class))
     */
    public $city;
    /**
     * @SWG\Property(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\Street::class))
     */
    public $street;
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\Coordinates::class))
     */
    public $coordinates;
    /**
     * @SWG\Property(type="string", description="Регион")
     */
    public $region;
    /**
     * @JMS\SerializedName("houseNumber")
     * @SWG\Property(type="string", description="Номер дома")
     */
    public $houseNumber;
    /**
     * @JMS\SerializedName("flatNumber")
     * @SWG\Property(type="string", description="Квартира")
     */
    public $flatNumber;
    /**
     * @SWG\Property(type="string", description="Подъезд")
     */
    public $entrance;
    /**
     * @SWG\Property(type="string", description="Домофон")
     */
    public $intercom;
    /**
     * @SWG\Property(type="string", description="Этаж")
     */
    public $floor;
    /**
     * @SWG\Property(type="string", description="Комментарий по адресу", example="Домофон не работает")
     */
    public $comment;
}