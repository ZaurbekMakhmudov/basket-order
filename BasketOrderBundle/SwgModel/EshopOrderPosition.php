<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 21.07.19
 * Time: 20:36
 */

namespace App\BasketOrderBundle\SwgModel;

use Swagger\Annotations as SWG;

/**
 * Class App\BasketOrderBundle\SwgModel\EshopOrderPosition
 * @package App\BasketOrderBundle\SwgModel
 */
class EshopOrderPosition
{
    /**
     * @SWG\Property(type="string", example="3086195")
     */
    private $product_id;
    /**
     * @SWG\Property(type="number", example="1")
     */
    private $product_amount;
    /**
     * @SWG\Property(type="number", example="102")
     */
    private $product_unit_price;
}