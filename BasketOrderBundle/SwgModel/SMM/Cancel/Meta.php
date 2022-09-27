<?php

namespace App\BasketOrderBundle\SwgModel\SMM\Cancel;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

class Meta
{

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("source")
     * @SWG\Property(type="string", description="Источник данных")
     */
    public $source;
}