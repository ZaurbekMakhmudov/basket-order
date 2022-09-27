<?php


namespace App\BasketOrderBundle\SwgModel\SMM;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/** Token
 * Class Token
 * @package App\BasketOrderBundle\SwgModel\SMM
 */
class Token
{
    /**
     * @Assert\NotBlank
     * @SWG\Property(property="token", type="string", maxLength=1024)
     */
    public $token;
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("expiresAt")
     * @SWG\Property(ref="#/definitions/DateTimeDef")
     */
    public $expiresAt;

}