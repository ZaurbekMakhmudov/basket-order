<?php

namespace App\BasketOrderBundle\SwgModel\SMM;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


class Address
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("source")
     * @SWG\Property(type="string", description="Источник")
     */
    public $source;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("postalCode")
     * @SWG\Property(type="string", description="Почтовый индекс")
     */
    public $postalCode;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("fias")
     * @SWG\Property(type="object", description="Данные по ФИАС",  ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\Fias::class))
     */
    public $fias;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("geo")
     * @SWG\Property(type="object", description="Гео данные	", ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\Geo::class))
     */
    public $geo;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("access")
     * @SWG\Property(type="object", description="Доступность", ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\Access::class))
     */
    public $access;


}