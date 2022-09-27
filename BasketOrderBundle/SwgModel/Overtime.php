<?php

namespace App\BasketOrderBundle\SwgModel;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Overtime
 * @package App\BasketOrderBundle\SwgModel
 */
class Overtime
{
    /**
     * @SWG\Property(type="string", example="Срок хранения 2 дня", description="Срок хранения, строка")
     */
    public $text;

    /**
     * @Assert\Regex("/^(\d{4})-(\d{2})-(\d{2})(T| )(\d{2}):(\d{2}):(\d{2})$/")
     * @SWG\Property(type="date-time", example="2020-01-01T01:01:01", description="Срок хранения, дата и время")
     */
    public $date;
}

