<?php

namespace App\BasketOrderBundle\SwgModel;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class App\BasketOrderBundle\SwgModel\CouponResult
 * @package App\BasketOrderBundle\SwgModel
 */
class CouponResult
{
    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^[0-9A-Za-zА-Яа-я_\-\!]+$/u")
     * @SWG\Property(type="string", example="45454545", description="Номер купона")
     */
    public $number;
    /**
     * 1
     * @SWG\Property(type="number", example="1", description="Тип купона")
     */
    public $type;
    /**
     * true
     * @SWG\Property(type="boolean", example="true", description="Флаг применённости купона")
     */
    public $applied;
    /**
     * @SWG\Property(type="string", example="Скидка за онлайн оплату", description="Наименование купона")
     */
    private $name;
}