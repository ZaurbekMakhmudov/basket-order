<?php

namespace App\BasketOrderBundle\SwgModel\DeliveryClub;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/** Оплата
 * Class Payment
 * @package App\BasketOrderBundle\SwgModel\DeliveryClub
 */
class Payment
{
    /**
     * @Assert\NotBlank
     * @Assert\Choice({"cash", "card", "online"})
     * @SWG\Property(type="string", enum={"cash", "card", "online"}, description="Тип оплаты, cash - оплата наличными, card - оплата картой, online - онлайн оплата")
     */
    public $type;
    /**
     * @JMS\SerializedName("requiredMoneyChange")
     * @SWG\Property(type="string", description="Сумма, с которой необходимо дать сдачу (целое число в десятичной записи)")
     */
    public $requiredMoneyChange;

}