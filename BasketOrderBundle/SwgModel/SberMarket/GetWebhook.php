<?php

namespace App\BasketOrderBundle\SwgModel\SberMarket;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


class GetWebhook
{

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("event_type")
     * @SWG\Property(type="string", description="Тип ивента")
     */
    public $eventType;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("payload")
     * @SWG\Property(type="object", description="Основная информация о заказе", ref=@Model(type=App\BasketOrderBundle\SwgModel\SberMarket\Payload::class))
     */
    public $payload;
}