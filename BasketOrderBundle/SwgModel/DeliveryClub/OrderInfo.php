<?php


namespace App\BasketOrderBundle\SwgModel\DeliveryClub;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/** Информация о заказе
 * Class OrderInfo
 * @package App\BasketOrderBundle\SwgModel\DeliveryClub
 */
class OrderInfo
{
    /**
     * @Assert\NotBlank
     * @SWG\Property(property="id", type="string", description="Идентификатор заказа во внешней системе")
     */
    public $id;
    /**
     * @Assert\NotBlank
     * @SWG\Property(ref="#/definitions/OrderStatusDef")
     */
    public $status;
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("expectedDateTime")
     * @SWG\Property(ref="#/definitions/DateTimeDef")
     */
    public $expectedDateTime;
    /**
     * @Assert\NotBlank
     * @SWG\Property(property="positions", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\OrderPosition::class)))
     */
    public $positions;
    /**
     * @Assert\NotBlank
     * @SWG\Property(property="total", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\DeliveryClub\Total::class))
     */
    public $total;
    /**
     * @JMS\SerializedName("shortCode")
     * @SWG\Property(property="shortCode", type="string", description="Короткий код для получения заказа")
     */
    public $shortCode;
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("changedOnFormed")
     * @SWG\Property(property="changedOnFormed", type="boolean", description="Вносились ли изменения при сборке заказа. Если true, то у изменившихся позиций должно быть заполнено formedQuantity.")
     */
    public $changedOnFormed;
    /**
     * @JMS\SerializedName("createdDateTime")
     * @SWG\Property(ref="#/definitions/DateTimeDef")
     */
    public $createdDateTime;
    /**
     * @JMS\SerializedName("startedDateTime")
     * @SWG\Property(ref="#/definitions/DateTimeDef")
     */
    public $startedDateTime;
    /**
     * @JMS\SerializedName("formedDateTime")
     * @SWG\Property(ref="#/definitions/DateTimeDef")
     */
    public $formedDateTime;
    /**
     * @JMS\SerializedName("deliveredDateTime")
     * @SWG\Property(ref="#/definitions/DateTimeDef")
     */
    public $deliveredDateTime;
    /**
     * @JMS\SerializedName("updatedDateTime")
     * @SWG\Property(ref="#/definitions/DateTimeDef")
     */
    public $updatedDateTime;

}