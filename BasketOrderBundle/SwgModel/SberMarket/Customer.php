<?php

namespace App\BasketOrderBundle\SwgModel\SberMarket;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


class Customer
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("name")
     * @SWG\Property(type="string", description="Имя клиента")
     */
    public $name;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("phone")
     * @SWG\Property(type="string", description="Номер телефона клиента")
     */
    public $phone;
}