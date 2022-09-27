<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 28.07.19
 * Time: 22:17
 */

namespace App\BasketOrderBundle\SwgModel;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Class App\BasketOrderBundle\SwgModel\Customer
 * @package App\BasketOrderBundle\SwgModel
 */
class Customer
{
    /**
     * @SWG\Property(type="string",example="Санкт-Петербург", description="Город конечного покупателя")
     */
    private $city;
    /**
     * @SWG\Property(type="string",example="453852", description="индекс конечного покупателя")
     */
    private $post_index;
    /**
     * @SWG\Property(type="string",example="Пушкина", description="улица конечного покупателя")
     */
    private $street;
    /**
     * @SWG\Property(type="string",example="1", description="")
     */
    private $building;
    /**
     * @SWG\Property(type="string",example="1", description="дом конечного покупателя")
     */
    private $house;
    /**
     * @SWG\Property(type="string",example="5", description="")
     */
    private $flat;
    /**
     * @SWG\Property(type="string",example="иванов и", description="ФИО конечного покупателя")
     */
    private $name;
    /**
     * @SWG\Property(type="string",example="+7 905 999 9999", description="Телефон конечного покупателя")
     */
    private $phone;
    /**
     * @SWG\Property(type="string",example="s@sddd.ru", description="E-mail конечного покупателя")
     */
    private $email;
    /**
     * @SWG\Property(type="string",example="2019-07-19", description="Дата доставки конечному покупателю")
     */
    private $date;
    /**
     * @SWG\Property(type="string",example="14:00", description="Желаемое время доставки конечному покупателю")
     */
    private $time;
    /**
     * @SWG\Property(type="string",example="2019-07-20", description="желаемая дата клиента")
     */
    private $desired_date;
    /**
     * @SWG\Property(type="string",example="11:00", description="желаемая дата клиента")
     */
    private $desired_time_from;
    /**
     * @SWG\Property(type="string",example="14:00", description="желаемая дата клиента")
     */
    private $desired_time_to;
    /**
     * @SWG\Property(type="string",example="", description="")
     */
    private $comment;
}