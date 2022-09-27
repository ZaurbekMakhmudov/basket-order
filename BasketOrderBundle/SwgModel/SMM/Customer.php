<?php

namespace App\BasketOrderBundle\SwgModel\SMM;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

class Customer
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("customerFullName")
     * @SWG\Property(type="string", description="ФИО Покупателя	")
     */
    public $customerFullName;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("phone")
     * @SWG\Property(type="string", description="Номер телефона покупателя в формате 7xxxxxxxxxx")
     */
    public $phone;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("email")
     * @SWG\Property(type="string", description="email покупателя")
     */
    public $email;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("address")
     * @SWG\Property(type="object", description="Адрес", ref=@Model(type=App\BasketOrderBundle\SwgModel\SMM\Address::class))
     */
    public $address;
}