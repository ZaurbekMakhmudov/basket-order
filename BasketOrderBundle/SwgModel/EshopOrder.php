<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 21.07.19
 * Time: 20:35
 */

namespace App\BasketOrderBundle\SwgModel;

use Swagger\Annotations as SWG;

/**
 * Class App\BasketOrderBundle\SwgModel\EshopOrder
 * @package App\BasketOrderBundle\SwgModel
 */
class EshopOrder
{
    /**
     * @var string
     * @SWG\Property(type="string", example="RCW")
     */
    private $order_status;
    /**
     * @var string
     * @SWG\Property(type="number", example=741.00)
     */
    private $order_sum;
}