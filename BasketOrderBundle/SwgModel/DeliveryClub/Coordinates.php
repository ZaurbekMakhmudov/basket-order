<?php

namespace App\BasketOrderBundle\SwgModel\DeliveryClub;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Coordinates
 * @package App\BasketOrderBundle\SwgModel\DeliveryClub
 */
class Coordinates
{
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="string", description="Широта")
     */
    public $latitude;
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="string", description="Долгота")
     */
    public $longitude;

}