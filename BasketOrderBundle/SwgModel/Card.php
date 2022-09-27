<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 24.07.19
 * Time: 19:19
 */

namespace App\BasketOrderBundle\SwgModel;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/*
 * App\BasketOrderBundle\Entity\Card
 * Class Card
 * @package App\BasketOrderBundle\Entity
 */

class Card
{
    /**
     * @SWG\Property(type="string", example="2775076098159", description="card number")
     */
    private $number;
    /**
     * @SWG\Property(type="string", example="4839.18", description="")
     */
    private $bonusbalance;
    /**
     * @SWG\Property(type="string", example="1", description="")
     */
    private $cardmode;
    /**
     * @SWG\Property(type="string", example="1", description="")
     */
    private $idcardgroup;
    /**
     * @SWG\Property(type="string", example="Тестовые карты", description="")
     */
    private $namecardgroup;
}