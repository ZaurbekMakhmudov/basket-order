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
 * Class App\BasketOrderBundle\SwgModel\itemAddItem
 * @package App\BasketOrderBundle\SwgModel
 */
class itemAddItem
{
    /**
     * @SWG\Property(type="integer", example=1)
     */
    private $quantity;
    /**
     * @SWG\Property(type="string", example="3110254")
     */
    private $barcode;
}