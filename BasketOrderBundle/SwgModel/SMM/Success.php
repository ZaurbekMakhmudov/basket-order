<?php

namespace App\BasketOrderBundle\SwgModel\SMM;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


class Success
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("success")
     * @SWG\Property(type="integer", description="")
     */
    public $success;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("meta")
     * @SWG\Property(type="object", description="Исходная информация от Продавца", ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\Cancel\Meta::class))
     */
    public $meta;
}