<?php

namespace App\BasketOrderBundle\SwgModel\SMM;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


class Access
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("detachedHouse")
     * @SWG\Property(type="boolean", description="Дом")
     */
    public $detachedHouse;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("entrance")
     * @SWG\Property(type="string", description="Вход, подъезд")
     */
    public $entrance;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("entrance")
     * @SWG\Property(type="integer", description="Этаж")
     */
    public $floor;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("intercom")
     * @SWG\Property(type="string", description="Этаж")
     */
    public $intercom;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("cargoElevator")
     * @SWG\Property(type="boolean", description="Лифт")
     */
    public $cargoElevator;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("comment")
     * @SWG\Property(type="string", description="Комментарий")
     */
    public $comment;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("apartment")
     * @SWG\Property(type="string", description="Квартира")
     */
    public $apartment;
}