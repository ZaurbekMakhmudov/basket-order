<?php

namespace App\BasketOrderBundle\SwgModel\DeliveryClub;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Status
 * @package App\BasketOrderBundle\SwgModel\DeliveryClub
 */
class Status
{
    /**
     * @Assert\NotBlank
     * @Assert\Choice({"created", "accepted", "handed_over_for_picking", "handed_over_for_delivery", "on_the_way", "delivered", "canceled"})
     * @SWG\Property(ref="#/definitions/OrderStatusDef")
     */
    public $status;
}