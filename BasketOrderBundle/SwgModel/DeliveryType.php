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
 * Class App\BasketOrderBundle\SwgModel\DeliveryType
 * @package App\BasketOrderBundle\SwgModel
 */
class DeliveryType
{
    /**
     * @SWG\Property(type="number",example="1", description="'1' - курьер; '2' - самовывоз")
     */
    private $type;
}