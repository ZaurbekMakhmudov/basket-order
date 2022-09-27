<?php

namespace App\BasketOrderBundle\SwgModel;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Class Actions
 * @package App\BasketOrderBundle\SwgModel
 */
class Actions
{
    /**
     * @SWG\Property(property="coupons", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\Action::class)))
     */
    private $coupons;
    /**
     * @SWG\Property(property="discounts", type="array", @SWG\Items(ref=@Model(type=App\BasketOrderBundle\SwgModel\Action::class)))
     */
    private $discounts;
}

