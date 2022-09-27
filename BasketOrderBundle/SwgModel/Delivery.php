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
 * Class App\BasketOrderBundle\SwgModel\Delivery
 * @package App\BasketOrderBundle\SwgModel
 */
class Delivery
{
    /**
     * @SWG\Property(type="string", example="1", description="Id ПВЗ в системе партнёра")
     */
    private $point_id;
    /**
     * @SWG\Property(type="string", example="1000043430", description="Код логагента в системе партнёра")
     */
    private $logagent_gln;
    /**
     * @SWG\Property(type="string", example="4607181504370", description="Код деливерипоинта ПВЗ в системе партнёра")
     */
    private $point_gln;
    /**
     * @SWG\Property(type="string", example="Магазин 'Улыбка Радуги' - Санкт-Петербург, пр. Ветеранов", description="Наименование ПВЗ")
     */
    private $name;
    /**
     * @SWG\Property(type="string", example="+7 905 999 9999", description="Телефон ПВЗ")
     */
    private $phone;
    /**
     * @SWG\Property(type="string", example="s@sddd.ru", description="E-mail ПВЗ")
     */
    private $email;
    /**
     * @SWG\Property(type="string", example="Санкт-Петербург, Ветеранов пр., д. 105", description="Адрес ПВЗ")
     */
    private $address;
    /**
     * @SWG\Property(type="number", format="float", example="99.00", description="Стоимость доставки")
     */
    private $cost_sum;
    /**
     * @SWG\Property(type="string", format="date", description="Дата доставки в ПВЗ")
     */
    private $point_date;
    /**
     * @SWG\Property(property="logagent", type="object", ref=@Model(type=App\BasketOrderBundle\SwgModel\Logagent::class))
     */
    private $logagent;
}
