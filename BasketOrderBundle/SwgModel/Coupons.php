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
 * Class App\BasketOrderBundle\SwgModel\Coupon
 * @package App\BasketOrderBundle\SwgModel
 */
class Coupons
{
    /**
     * @SWG\Property(property="coupon", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\Coupon::class)))
     */
    private $coupon;
}