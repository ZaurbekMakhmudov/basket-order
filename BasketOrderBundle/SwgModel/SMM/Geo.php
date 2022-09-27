<?php

namespace App\BasketOrderBundle\SwgModel\SMM;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


class Geo
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("lat")
     * @SWG\Property(type="string", description="Широта")
     */
    public $lat;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("lon")
     * @SWG\Property(type="string", description="Долгота")
     */
    public $lon;
}