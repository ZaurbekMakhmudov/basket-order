<?php

namespace App\BasketOrderBundle\Entity;

use App\BasketOrderBundle\Helper\AppHelper;
use App\BasketOrderBundle\Helper\DateTimeHelper;
use App\BasketOrderBundle\Helper\ShopConst;
use App\BasketOrderBundle\Model\BaseEntity;
use App\BasketOrderBundle\Traits\_CustomerTrait;
use App\BasketOrderBundle\Traits\_DeliveryTrait;
use App\BasketOrderBundle\Traits\_EshopOrderTrait;
use App\BasketOrderBundle\Traits\_PaymentInformationTrait;
use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use JMS\Serializer\Annotation\Type;

/**
 * @ORM\Table(name="buy_order", options={"comment" = "заказы" }, indexes={@ORM\Index(name="IDX_ORDER_ORDER_ID", columns={"order_id"})})
 * @ORM\Entity(repositoryClass="App\BasketOrderBundle\Repository\OrderRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Order extends BaseEntity
{
    use _DeliveryTrait;
    use _CustomerTrait;
    use _EshopOrderTrait;
    use _PaymentInformationTrait;
    /**
     * @var integer
     */
    private $eShopOrdersCount;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * номер заказа
     * @ORM\Column(name="order_id",length=50,type="string", options={"comment" = "номер заказа" })
     */
    protected $orderId;

    /**
     * номер заказа партнера
     * @ORM\Column(name="order_id_partner", type="string", length=255, nullable=true, options={"comment" = "номер заказа партнера" })
     */
    protected $orderIdPartner;

    /**
     * ID пользователя
     * @ORM\Column(name="user_id",type="string", options={"comment" = "ID пользователя приложения" })
     */
    protected $userId;

    /**
     * статус заказа
     * @ORM\Column(name="status",length=5,type="string", options={"comment" = "Статус заказа в eshop" })
     */
    protected $status;

    /**
     * дата создания заказа
     * @ORM\Column(name="created",type="datetime", options={"comment" = "дата создания заказа" })
     */
    protected $created;

    /**
     * дата обновления заказа
     * @ORM\Column(name="updated",type="datetime", options={"comment" = "дата обновления заказа" })
     */
    protected $updated;

    /**
     * дата последнего калькулейта
     * @ORM\Column(name="calculate", type="datetime", nullable=true, options={"comment" = "дата последнего калькулейта" })
     */
    protected $calculate;

    /**
     * дата последнего генерэйта
     * @ORM\Column(name="generate", type="datetime", nullable=true, options={"comment" = "дата последнего генерэйта" })
     */
    protected $generate;

    /**
     * скидка по заказу
     * @ORM\Column(name="discount",type="decimal", precision=15, scale=2, nullable=true, options={"comment" = "скидка по заказу" })
     */
    protected $discount;

    /**
     * Тип оплаты
     * @ORM\Column(name="payment_type",type="string", length=5, nullable=true, options={"comment" = "Тип оплаты" })
     */
    protected $paymentType;

    /**
     * стоимость без скидки
     * @SWG\Property(type="number", format="float", example=488.50)
     * @ORM\Column(name="price",type="decimal", precision=15, scale=2, nullable=true, options={"comment" = "стоимость без скидки" })
     * @Type("string")
     */
    protected $price;

    /**
     * стоимость со скидкой
     * @SWG\Property(type="number", format="float", example=488.50)
     * @ORM\Column(name="cost",type="decimal", precision=15, scale=2, nullable=true, options={"comment" = "стоимость со скидкой" })
     * @Type("string")
     */
    protected $cost;

    /**
     * акции по заказу
     * @SWG\Property(property="actions", type="object", description="данные об акциях", ref=@Model(type=App\BasketOrderBundle\SwgModel\Actions::class))
     * @ORM\Column(name="actions",type="text", nullable=true, options={"comment" = "акции по заказу" })
     */
    protected $actions;

    /**
     * примечание к заказу
     * @ORM\Column(name="comment",type="string", nullable=true, options={"comment" = "примечание к заказу в целом" })
     */
    protected $comment;

    /**
     * данные пользователя
     * @SWG\Property(property="customer", type="object", description="данные пользователя", ref=@Model(type=App\BasketOrderBundle\SwgModel\Customer::class))
     * @ORM\Column(name="customer",type="text", nullable=true, options={"comment" = "данные пользователя" })
     */
    protected $customer;

    /**
     * Способ доставки
     * @ORM\Column(name="delivery_type",type="string", nullable=true, options={"comment" = "Способ доставки: Доставка или Самовывоз" })
     */
    protected $deliveryType;

    /**
     * Схема доставки
     * @ORM\Column(name="delivery_scheme",type="smallint", nullable=true, options={"comment" = "Схема доставки:  1 контурная - курьер, 2 контурная - доставка УР" })
     */
    protected $deliveryScheme;

    /**
     * код логагента в системе партнёра
     * @ORM\Column(name="logagent_gln",type="string", nullable=true, options={"comment" = "Код логагента в системе партнёра" })
     */
    protected $logagentGln;

    /**
     * данные логагента
     * @ORM\Column(name="logagent_data",type="text", nullable=true, options={"comment" = "" })
     */
    protected $logagentData;

    /**
     * ИД службы доставки
     * @ORM\Column(name="delivery_point_id",type="string", nullable=true, options={"comment" = "ИД службы доставки" })
     */
    protected $deliveryPointId;

    /**
     * код деливерипоинта в системе партнёра
     * @ORM\Column(name="delivery_point_gln",type="string", nullable=true, options={"comment" = "Код деливерипоинта в системе партнёра" })
     */
    protected $deliveryPointGln;

    /**
     * данные по доставке
     * @SWG\Property(property="delivery", type="object", description="данные по доставке", ref=@Model(type=App\BasketOrderBundle\SwgModel\Delivery::class))
     * @ORM\Column(name="delivery",type="text", nullable=true, options={"comment" = "данные по доставке" })
     */
    protected $delivery;

    /**
     * стоимость доставки
     * @SWG\Property(type="string", example="99.00")
     * @ORM\Column(name="delivery_cost_sum", type="string", nullable=true, options={"comment" = "Стоимость доставки" })
     */
    protected $deliveryCostSum;

    /**
     * стоимость доставки партнера
     * @ORM\Column(name="delivery_cost_sum_partner", type="string", length=255, nullable=true, options={"comment" = "Стоимость доставки партнера" })
     */
    protected $deliveryCostSumPartner;

    /**
     * @ORM\Column(name="packet_id",type="string", nullable=true, options={"comment" = "" })
     */
    protected $packetId;

    /**
     * идентификатор источника заказа
     * @ORM\Column(name="source_identifier",type="string", nullable=true, options={"comment" = "Идентификатор источника заказа" })
     */
    protected $sourceIdentifier;

    /**
     * идентификатор прайс-листа
     * @ORM\Column(name="product_pricelist_id",length=5, type="string", nullable=true, options={"comment" = "Идентификатор прайс-листа" })
     */
    protected $productPricelistId;

    /**
     * Дополнительный параметр к типу цены
     * @ORM\Column(name="product_pricelist_param",length=20,type="string", nullable=true, options={"comment" = "Дополнительный параметр к типу цены" })
     */
    protected $productPricelistParam;

    /**
     * @ORM\Column(name="era_date",type="datetime", nullable=true, options={"comment" = "" })
     */
    protected $eraDate;

    /**
     * @ORM\Column(name="eshop_date",type="datetime", nullable=true, options={"comment" = "" })
     */
    protected $eshopDate;

    /**
     * сообщение об ошибке
     * @ORM\Column(name="era_error_message",type="string", nullable=true, options={"comment" = "Сообщение об ошибке обработки от Эры" })
     */
    protected $eraErrorMessage;

    /**
     * @ORM\Column(name="eshop_error_message",type="string", nullable=true, options={"comment" = "" })
     */
    protected $eshopErrorMessage;

    /**
     * @ORM\Column(name="date_insert",type="datetime", nullable=true, options={"comment" = "" })
     */
    protected $dateInsert;

    /**
     * статус оплаты
     * @ORM\Column(name="pay_status",type="boolean", nullable=true, options={"comment" = "Статус оплаты, 0-не оплачено, 1 -оплачено" })
     */
    protected $payStatus;

    /**
     * дата оплаты заказа
     * @ORM\Column(name="payed",type="datetime", nullable=true, options={"comment" = "дата оплаты заказа" })
     */
    protected $payed;

    /**
     * сумма платежа
     * @ORM\Column(name="pay_sum",type="decimal", precision=18, scale=2, nullable=true, options={"comment" = "сумма платежа" })
     */
    protected $paySum;

    /**
     * данные по оплате
     * @SWG\Property(property="payment_information", type="object", description="данные по оплате", ref=@Model(type=App\BasketOrderBundle\SwgModel\PaymentInformation::class))
     * @ORM\Column(name="payment_information",type="json_array", nullable=true, options={"comment" = "данные по оплате" })
     */
    protected $paymentInformation;

    /**
     * данные о сроке хранения
     * @SWG\Property(property="overtime", type="object", description="данные о сроке хранения", ref=@Model(type=App\BasketOrderBundle\SwgModel\Overtime::class))
     * @ORM\Column(name="overtime",type="json_array", nullable=true, options={"comment" = "данные о сроке хранения" })
     */
    protected $overtime;

    private $itemsCount;
    private $basketObject;
    private $itemArray;
    private $isFlush;
    private $orderSum;
    private $confirm = false;

    /**
     * Order constructor.
     */
    function __construct()
    {
        $this->status = ShopConst::STATUS_DRAFT;
        $this->created = DateTimeHelper::getInstance()->getDateCurrent();
        $this->updated = DateTimeHelper::getInstance()->getDateCurrent();
        $this->orderId = '';
    }

    /**
     * @ORM\PreFlush()
     */
    public function preFlush()
    {
//        $this->updated = DateTimeHelper::getInstance()->getDateCurrent();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;
    }

    public function getActions()
    {
        if($this->actions) {

            return json_decode($this->actions, true);
        }

        return null;
    }

    public function setActions(?array $actions)
    {
        $this->actions = $actions ? json_encode($actions, JSON_UNESCAPED_UNICODE) : null;
    }

    public function getDiscount()
    {
        return $this->discount;
    }

    public function setDiscount($discount)
    {
        $this->discount = $discount;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return mixed
     */
    public function getCost()
    {
        if ($this->cost) {
            return $this->cost;
        } else {
            return $this->price;
        }
    }

    public function setCost($cost)
    {
        $this->cost = $cost;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    public function getPaymentType()
    {
        return $this->paymentType;
    }

    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function getPacketId()
    {
        return $this->packetId;
    }

    public function setPacketId($packetId)
    {
        $this->packetId = $packetId;
    }

    public function getSourceIdentifier()
    {
        return $this->sourceIdentifier;
    }

    public function setSourceIdentifier($sourceIdentifier)
    {
        $this->sourceIdentifier = $sourceIdentifier;
    }

    public function getProductPricelistId()
    {
        return $this->productPricelistId;
    }

    public function setProductPricelistId($productPricelistId)
    {
        $this->productPricelistId = $productPricelistId;
    }

    public function getProductPricelistParam()
    {
        return $this->productPricelistParam;
    }

    public function setProductPricelistParam($productPricelistParam)
    {
        $this->productPricelistParam = $productPricelistParam;
    }

    public function getEraDate()
    {
        return $this->eraDate;
    }

    public function setEraDate($eraDate)
    {
        $this->eraDate = $eraDate;
    }

    /** @return \DateTime */
    public function getEshopDate()
    {
        return $this->eshopDate;
    }

    public function setEshopDate($eshopDate)
    {
        $this->eshopDate = $eshopDate;
    }

    public function getEraErrorMessage()
    {
        return $this->eraErrorMessage;
    }

    public function setEraErrorMessage($eraErrorMessage)
    {
        $this->eraErrorMessage = $eraErrorMessage;
    }

    public function getEshopErrorMessage()
    {
        return $this->eshopErrorMessage;
    }

    public function setEshopErrorMessage($eshopErrorMessage)
    {
        $this->eshopErrorMessage = $eshopErrorMessage;
    }

    public function getDateInsert()
    {
        return $this->dateInsert;
    }

    public function setDateInsert($dateInsert)
    {
        $this->dateInsert = $dateInsert;
    }

    public function getDeliveryType()
    {
        return $this->deliveryType;
    }

    public function setDeliveryType($deliveryType)
    {
        $this->deliveryType = $deliveryType;
    }

    public function getLogagentGln()
    {
        return $this->logagentGln;
    }

    public function getDeliveryCostSum()
    {
        return $this->deliveryCostSum;
    }

    public function setDeliveryCostSum($deliveryCostSum)
    {
        $this->deliveryCostSum = $deliveryCostSum;
    }

    public function getDeliveryPointGln()
    {
        return $this->deliveryPointGln;
    }

    /**
     *
     */
    public function setStatusCre()
    {
        if ($this->status == ShopConst::STATUS_DRAFT) {
            $this->status = ShopConst::STATUS_CRE;
        }
    }

    /**
     * @param $data
     */
    public function setLogagent($data)
    {
        $out = [];
        (isset($data['name'])) ? $out['name'] = $data['name'] : ($this->getDeliveryLogagentName() ? $out['name'] = $this->getDeliveryLogagentName() : null);
        (isset($data['phone'])) ? $out['phone'] = $data['phone'] : ($this->getDeliveryLogagentPhone() ? $out['phone'] = $this->getDeliveryLogagentPhone() : null);
        (isset($data['email'])) ? $out['email'] = $data['email'] : ($this->getDeliveryLogagentEmail() ? $out['email'] = $this->getDeliveryLogagentEmail() : null);
        (isset($data['date'])) ? $out['date'] = $data['date'] : ($this->getDeliveryLogagentDate() ? $out['date'] = $this->getDeliveryLogagentDate()->format('Y-m-d') : null);
        (isset($data['time'])) ? $out['time'] = $data['time'] : ($this->getDeliveryLogagentTime() ? $out['time'] = $this->getDeliveryLogagentTime() : null);

        $out ? $this->setLogagentData($out) : null;
    }

    /**
     * @param $data
     */
    public function setDeliveryData($data)
    {
        $delivery = [];
        (isset($data['name'])) ? $delivery['name'] = $data['name'] : ($this->getDeliveryName() ? $delivery['name'] = $this->getDeliveryName() : null);
        (isset($data['phone'])) ? $delivery['phone'] = $data['phone'] : ($this->getDeliveryPhone() ? $delivery['phone'] = $this->getDeliveryPhone() : null);
        (isset($data['email'])) ? $delivery['email'] = $data['email'] : ($this->getDeliveryEmail() ? $delivery['email'] = $this->getDeliveryEmail() : null);
        (isset($data['address'])) ? $delivery['address'] = $data['address'] : ($this->getDeliveryAddress() ? $delivery['address'] = $this->getDeliveryAddress() : null);

        $delivery ? $this->setDelivery($delivery) : null;
        (isset($data['logagent_gln'])) ? $this->logagentGln = $data['logagent_gln'] : null;
        (isset($data['point_gln'])) ? $this->deliveryPointGln = $data['point_gln'] : null;
        (isset($data['cost_sum'])) ? $this->deliveryCostSum = $data['cost_sum'] : null;
        (isset($data['point_id'])) ? $this->deliveryPointId = $data['point_id'] : null;
    }

    /**
     * @return int
     */
    public function getItemsCount()
    {
        return $this->itemsCount;
    }

    /**
     * @param int $itemsCount
     */
    public function setItemsCount($itemsCount)
    {
        $this->itemsCount = $itemsCount;
    }

    /**
     * @return \App\BasketOrderBundle\Entity\Basket
     */
    public function getBasketObject()
    {
        return $this->basketObject;
    }

    /**
     * @param \App\BasketOrderBundle\Entity\Basket $basketObject
     */
    public function setBasketObject($basketObject)
    {
        $this->basketObject = $basketObject;
    }

    /**
     * @return array
     */
    public function getItemArray()
    {
        return $this->itemArray;
    }

    /**
     * @param array $itemArray
     */
    public function setItemArray($itemArray)
    {
        $this->itemArray = $itemArray;
    }

    /**
     * @return bool
     */
    public function isIsFlush()
    {
        return $this->isFlush;
    }

    /**
     * @param bool $isFlush
     */
    public function setIsFlush($isFlush)
    {
        $this->isFlush = $isFlush;
    }

    /**
     * @return mixed
     */
    public function getDeliveryPointId()
    {
        return $this->deliveryPointId;
    }

    /**
     * @param mixed $deliveryPointId
     */
    public function setDeliveryPointId($deliveryPointId)
    {
        $this->deliveryPointId = $deliveryPointId;
    }

    /**
     * @return mixed
     */
    public function getPayStatus()
    {
        return $this->payStatus;
    }

    /**
     * @param mixed $payStatus
     */
    public function setPayStatus($payStatus)
    {
        $this->payStatus = $payStatus;
    }

    /**
     * @return \DateTime
     */
    public function getPayed()
    {
        return $this->payed;
    }

    /**
     * @param \DateTime $payed
     */
    public function setPayed(\DateTime $payed)
    {
        $this->payed = $payed;
    }

    /**
     * @return mixed
     */
    public function getPaySum()
    {
        return $this->paySum;
    }

    /**
     * @param mixed $paySum
     */
    public function setPaySum($paySum)
    {
        $this->paySum = $paySum;
    }

    /**
     * @param $requestBody
     */
    public function updatePayStatus($requestBody)
    {
        $payStatus = isset($requestBody['pay_status']) ? $requestBody['pay_status'] : null;
        $paySum = isset($requestBody['pay_sum']) ? $requestBody['pay_sum'] : null;

        if ($payStatus and !$this->payStatus) {
            $this->payed = DateTimeHelper::getInstance()->getDateCurrent();
            $this->payStatus = (boolean)$payStatus;
            $this->paySum = $paySum;
        }
    }

    /**
     * @return int
     */
    public function getEShopOrdersCount()
    {
        return $this->eShopOrdersCount;
    }

    /**
     * @param int $eShopOrdersCount
     */
    public function setEShopOrdersCount($eShopOrdersCount)
    {
        $this->eShopOrdersCount = $eShopOrdersCount;
    }

    /**
     * @return float
     */
    public function getOrderSum()
    {
        return $this->orderSum;
    }

    /**
     * @param float $orderSum
     */
    public function setOrderSum($orderSum)
    {
        $this->orderSum = $orderSum;
    }

    /**
     * @return bool
     */
    public function isConfirm()
    {
        return $this->confirm;
    }

    /**
     * @param $confirm
     */
    public function setConfirm($confirm)
    {
        $this->confirm = $confirm;
    }

    public function setDigitalRound()
    {
        $this->setPrice(AppHelper::getDigitalRound($this->getPrice()));
        $this->setCost(AppHelper::getDigitalRound($this->getCost()));
        $this->deliveryCostSum = AppHelper::getDigitalRound($this->getDeliveryCostSum());
    }

    public function getOrderIdPartner(): ?string
    {
        return $this->orderIdPartner;
    }

    public function setOrderIdPartner(?string $orderIdPartner): self
    {
        $this->orderIdPartner = $orderIdPartner;

        return $this;
    }

    public function getDeliveryCostSumPartner(): ?string
    {
        return $this->deliveryCostSumPartner;
    }

    public function setDeliveryCostSumPartner(?string $deliveryCostSumPartner): self
    {
        $this->deliveryCostSumPartner = $deliveryCostSumPartner;

        return $this;
    }

    public function getCalculate()
    {
        return $this->calculate;
    }

    public function setCalculate(\DateTime $calculate): void
    {
        $this->calculate = $calculate;
    }

    public function getGenerate()
    {
        return $this->generate;
    }

    public function setGenerate(\DateTime $generate): void
    {
        $this->generate = $generate;
    }

    public function getDeliveryScheme()
    {
        return $this->deliveryScheme;
    }

    public function setDeliveryScheme($deliveryScheme)
    {
        $this->deliveryScheme = $deliveryScheme;
    }

    public function getOvertime()
    {
        return $this->overtime;
    }

    public function setOvertime($overtime)
    {
        $this->overtime = $overtime;
    }

    public function setOvertimeText(string $overtimeText)
    {
        $overtime = $this->getOvertime();
        $overtime['text'] = $overtimeText;
        $this->setOvertime($overtime); // todo: fix unicode string info DB
    }

    public function setOvertimeDate(string $overtimeDate)
    {
        $overtime = $this->getOvertime();
        $overtime['date'] = $overtimeDate;
        $this->setOvertime($overtime);
    }

}
