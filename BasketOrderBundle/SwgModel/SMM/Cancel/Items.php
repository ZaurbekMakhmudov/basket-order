<?php

namespace App\BasketOrderBundle\SwgModel\SMM\Cancel;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

class Items
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("itemIndex")
     * @SWG\Property(type="string", description="Порядковый номер лота")
     */
    public $itemIndex;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("goodsId")
     * @SWG\Property(type="string", description="Идентификатор карточки товара СберМегаМаркет")
     */
    public $goodsId;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("offerId")
     * @SWG\Property(type="string", description="Идентификатор оффера продавца")
     */
    public $offerId;
}