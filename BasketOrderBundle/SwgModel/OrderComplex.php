<?php

namespace App\BasketOrderBundle\SwgModel;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class OrderComplex
 * @package App\BasketOrderBundle\SwgModel
 */
class OrderComplex
{
    /**
     * @Assert\NotBlank
     * @Assert\Choice({"0", "1"})
     * @SWG\Property(property="payment_type", type="string", description="способ оплаты: 0-наличными, 1-онлайн", example="1")
     */
    public $payment_type;
    /**
     * @Assert\NotBlank
     * @Assert\Choice({"1", "2", "10", "12", "13"})
     * @SWG\Property(property="delivery_type", type="string", description="способ доставки: 1-курьер, 2-самовывоз, 10-PickUpInStore, 12-PickUpInStoreExpress, 13 - AssemblyPartner", example="2")
     */
    public $delivery_type;
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\Customer::class))
     */
    public $customer;
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\Delivery::class))
     */
    public $delivery;
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\itemAdd::class)))
     */
    public $items;
    /**
     * @Assert\Regex("/^(27\d{11})$/")
     * @SWG\Property(property="card", type="string", example="2775076098159", description="Номер карты лояльности")
     */
    public $card;

}
