<?php

namespace App\BasketOrderBundle\SwgModel\SMM;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

class OrderCancel
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("data")
     * @SWG\Property(type="object", description="Общий блок с данными", ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\Cancel\Data::class))
     */
    public $data;


    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("meta")
     * @SWG\Property(type="object", description="Исходная информация от Продавца", ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\Cancel\Meta::class))
     */
    public $meta;

}