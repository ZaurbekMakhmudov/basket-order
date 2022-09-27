<?php


namespace App\BasketOrderBundle\SwgModel\DeliveryClub;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/** Информация о статусе заказа
 * Class OrderStatus
 * @package App\BasketOrderBundle\SwgModel\DeliveryClub
 */
class OrderStatus
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
}