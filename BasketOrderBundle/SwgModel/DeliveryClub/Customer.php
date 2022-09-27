<?php

namespace App\BasketOrderBundle\SwgModel\DeliveryClub;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/** Клиент
 * Class Customer
 * @package App\BasketOrderBundle\SwgModel\DeliveryClub
 */
class Customer
{
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="string", description="Имя клиента")
     */
    public $name;
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="string", description="Телефон клиента")
     */
    public $phone;

}