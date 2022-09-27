<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 29.07.19
 * Time: 8:57
 */

namespace App\BasketOrderBundle\SwgModel;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Class App\BasketOrderBundle\SwgModel\Logagent
 * @package App\BasketOrderBundle\SwgModel
 */
class Logagent
{
    /**
     * @SWG\Property(type="string", example="Логистика Сервис", description="Наименование логагента")
     */
    private $name;
    /**
     * @SWG\Property(type="string", example="+7 905 999 9999", description="Телефон логагента")
     */
    private $phone;
    /**
     * @SWG\Property(type="string", example="s@sddd.ru", description="E-mail логагента")
     */
    private $email;
    /**
     * @SWG\Property(type="string", format="date", example="2019-07-22", description="Дата доставки логагенту")
     */
    private $date;
    /**
     * @SWG\Property(type="string", example="15:00", description="Желаемое время доставки логагенту (строка)")
     */
    private $time;
}