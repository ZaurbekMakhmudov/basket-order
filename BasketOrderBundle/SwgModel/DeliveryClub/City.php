<?php

namespace App\BasketOrderBundle\SwgModel\DeliveryClub;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/** Город
 * Class City
 * @package App\BasketOrderBundle\SwgModel\DeliveryClub
 */
class City
{
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="string", description="Название города")
     */
    public $name;
    /**
     * @SWG\Property(type="string", description="Код города")
     */
    public $code;

}