<?php

namespace App\BasketOrderBundle\SwgModel\SberMarket;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


class Success
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("status")
     * @SWG\Property(type="string", description="Метод")
     */
    public $status;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("number")
     * @SWG\Property(type="string", description="Партнерский ID заказа")
     */
    public $number;
}