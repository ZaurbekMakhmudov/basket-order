<?php

namespace App\BasketOrderBundle\SwgModel\SberMarket;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


class Total
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("totalPrice")
     * @SWG\Property(type="string", description="Итоговая цена")
     */
    public $totalPrice;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("totalDiscountPrice")
     * @SWG\Property(type="string", description="Итоговая скидка")
     */
    public $totalDiscountPrice;
}