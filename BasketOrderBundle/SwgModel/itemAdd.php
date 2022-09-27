<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 28.07.19
 * Time: 22:17
 */

namespace App\BasketOrderBundle\SwgModel;

use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;

/**
 * Class App\BasketOrderBundle\SwgModel\itemAdd
 * @package App\BasketOrderBundle\SwgModel
 */
class itemAdd
{
    /**
     * @SWG\Property(type="string",
     *     example="Лак для ногтей NailLook Trends Perfect Match 31921 Holy&Jolly 3мл * 2шт")
     */
    private $name;
    /**
     * @SWG\Property(type="number", example=193)
     */
    private $price;
    /**
     * @SWG\Property(type="integer", example=1)
     */
    private $quantity;
    /**
     * @SWG\Property(type="string", example="3110254")
     */
    private $article;
    /**
     * @SWG\Property(type="string", example="https://www.r-ulybka.ru/upload/procream/images/goods/3110254_12_370.jpg")
     */
    private $productImageUrl;
    /**
     * @SWG\Property(type="number", example=1.2)
     */
    private $weight;
    /**
     * @SWG\Property(type="number", example=1.235)
     */
    private $volume;

//*          @SWG\Property(property="anonim_id", type="string", description="Field description anonim_id"),
//*          @SWG\Property(property="item", type="object", ref=@Model(type=App\BasketOrderBundle\Entity\Item::class))

}