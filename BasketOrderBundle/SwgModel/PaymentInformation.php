<?php

namespace App\BasketOrderBundle\SwgModel;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Payment
 * @package App\BasketOrderBundle\SwgModel
 */
class PaymentInformation
{
    /**
     * @Assert\NotBlank
     * @Assert\Choice({-1, 1})
     * @SWG\Property(type="integer", example=1, description="Статус оплаты", enum={1, -1})
     */
    public $status;
    /**
     * @Assert\NotBlank
     * @SWG\Property(type="float", example=100, description="Сумма оплаты")
     */
    public $amount;
    /**
     * @SWG\Property(type="date-time", example="2020-01-01 01:01:01", description="Дата и время оплаты")
     */
    public $date;
}
