<?php

namespace App\BasketOrderBundle\SwgModel\DeliveryClub;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/** Улица
 * Class Street
 * @package App\BasketOrderBundle\SwgModel\DeliveryClub
 */
class Street
{
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="string", description="Название улицы")
     */
    public $name;
    /**
     * @SWG\Property(type="string", description="Код улицы")
     */
    public $code;

}