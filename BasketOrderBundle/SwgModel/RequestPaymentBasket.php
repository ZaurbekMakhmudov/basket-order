<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 24.07.19
 * Time: 19:22
 */

namespace App\BasketOrderBundle\SwgModel;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Class App\BasketOrderBundle\SwgModel\RequestPaymentBasket
 * @package App\BasketOrderBundle\SwgModel
 */
class RequestPaymentBasket
{
    /**
     * @SWG\Property(type="string", example="0", description="payment_type")
     */
    private $payment_type;
}