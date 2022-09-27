<?php

namespace App\BasketOrderBundle\SwgModel\SMM;

use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

class Handover
{
    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("packingDate")
     * @SWG\Property(type="string", description="Дата, к которой необходимо скомплектовать отправление	")
     */
    public $packingDate;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("reserveExpirationDate")
     * @SWG\Property(type="string", description="Дата истечения срока резерва в магазинe")
     */
    public $reserveExpirationDate;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("outletId")
     * @SWG\Property(type="string", description="Идентификатор магазина выдачи по системе продавца	")
     */
    public $outletId;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("serviceScheme")
     * @SWG\Property(type="string", description="Идентификатор схемы. Для Click&Collect всегда = COLLECT_BY_CUSTOMER. 	")
     */
    public $serviceScheme;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("depositedAmount")
     * @SWG\Property(type="integer", description="Сумма которую должен получить СберМегаМаркет по предоплате от покупателя (товар+доставка)")
     */
    public $depositedAmount;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("deliveryInterval")
     * @SWG\Property(type="string", description="Интервал доставки")
     */
    public $deliveryInterval;

    /**
     * @Assert\NotBlank
     * @JMS\SerializedName("deliveryId")
     * @SWG\Property(type="integer", description="Номер доставки СберМегаМаркет")
     */
    public $deliveryId;


}