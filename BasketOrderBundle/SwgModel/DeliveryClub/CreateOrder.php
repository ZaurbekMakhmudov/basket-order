<?php


namespace App\BasketOrderBundle\SwgModel\DeliveryClub;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class CreateOrder
 * @package App\BasketOrderBundle\SwgModel\DeliveryClub
 */
class CreateOrder
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("originalOrderId")
     * @SWG\Property(type="string", description="Идентификатор заказа созданный на стороне агрегатора")
     */
    public $originalOrderId;
    /**
     * @Assert\NotBlank
     * @SWG\Property(property="customer", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\Customer::class))
     */
    public $customer;
    /**
     * @Assert\NotBlank
     * @SWG\Property(property="delivery", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\Delivery::class))
     */
    public $delivery;
    /**
     * @JMS\SerializedName("replacementType")
     * @Assert\Choice({"call_remove", "call_select", "dont_call_remove", "dont_call_select"})
     * @SWG\Property(property="replacementType", type="string", enum={"call_remove", "call_select", "dont_call_remove", "dont_call_select"}, description="ID выбранного типа замены отсутствующего товара. call_remove - позвонить, если не дозвонились - убрать товар. call_select - позвонить, если не дозвонились - замену выбирает сборщик. dont_call_remove - не звонить, убрать отсутствующий товар. dont_call_select - не звонить, замену выбирает сборщик.")
     */
    public $replacementType;
    /**
     * @Assert\NotBlank
     * @SWG\Property(property="payment", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\Payment::class))
     */
    public $payment;
    /**
     * @Assert\NotBlank
     * @SWG\Property(property="positions", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\Position::class)))
     */
    public $positions;
    /**
     * @Assert\NotBlank
     * @SWG\Property(property="total", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\Total::class))
     */
    public $total;
    /**
     * @SWG\Property(property="comment", type="string", description="Комментарий к заказу")
     */
    public $comment;
    /**
     * @JMS\SerializedName("loyaltyCard")
     * @SWG\Property(property="loyaltyCard", type="string", description="Номер карты лояльности, если привязана")
     */
    public $loyaltyCard;
}