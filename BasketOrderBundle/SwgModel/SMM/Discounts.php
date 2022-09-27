<?php

namespace App\BasketOrderBundle\SwgModel\SMM;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

class Discounts
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("discountType")
     * @SWG\Property(type="string", description="Тип скидки")
     */
    public $discountType;


    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("discountDescription")
     * @SWG\Property(type="string", description="Наименование скидки")
     */
    public $discountDescription;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("discountAmount")
     * @SWG\Property(type="integer", description="Сумма скидки ")
     */
    public $discountAmount;
}