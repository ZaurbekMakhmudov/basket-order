<?php

namespace App\BasketOrderBundle\SwgModel\SMM;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;



class Shipments
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("shipmentId")
     * @SWG\Property(type="string", description="Идентификатор отправления СберМегаМаркет")
     */
    public $shipmentId;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("shipmentDate")
     * @SWG\Property(type="string", description="Дата создания отправления")
     */
    public $shipmentDate;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("items")
     * @SWG\Property(type="array", property="items", description="Данные о лотах", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\Items::class)))
     */
    public $items;
}