<?php


namespace App\BasketOrderBundle\SwgModel;

use Swagger\Annotations as SWG;

/**
 * Class Action
 * @package App\BasketOrderBundle\SwgModel
 */
class Action
{
    /**
     * @SWG\Property(type="string", example="2772999011814", description="")
     */
    private $code;
    /**
     * @SWG\Property(type="string", example="Скидка за онлайн оплату", description="")
     */
    private $name;
}