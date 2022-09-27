<?php

namespace App\BasketOrderBundle\SwgModel\SberMarket;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


class Delivery
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("expectedFrom")
     * @SWG\Property(type="string", description="Ожидается от")
     */
    public $expectedFrom;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("expectedTo")
     * @SWG\Property(type="string", description="Ожидается до")
     */
    public $expectedTo;
}