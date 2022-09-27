<?php

namespace App\BasketOrderBundle\SwgModel\SMM\Cancel;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

class Data
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("merchantId")
     * @SWG\Property(type="integer", description="Идентификатор Продавца на стороне СберМегаМаркет")
     */
    public $merchantId;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("shipments")
     * @SWG\Property(type="array", property="shipments", description="Данные об отправлениях", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\Cancel\Shipments::class)))
     */
    public $shipments;
}