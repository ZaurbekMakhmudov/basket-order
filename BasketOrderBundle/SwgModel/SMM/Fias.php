<?php

namespace App\BasketOrderBundle\SwgModel\SMM;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


class Fias
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("regionId")
     * @SWG\Property(type="string", description="Код региона")
     */
    public $regionId;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("destination")
     * @SWG\Property(type="string", description="Пункт назначения")
     */
    public $destination;
}