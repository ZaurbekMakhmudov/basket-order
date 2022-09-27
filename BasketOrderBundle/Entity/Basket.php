<?php

namespace App\BasketOrderBundle\Entity;

use App\BasketOrderBundle\Helper\AppHelper;
use App\BasketOrderBundle\Helper\DateTimeHelper;
use App\BasketOrderBundle\Helper\ShopConst;
use App\BasketOrderBundle\Model\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Exclude;

/**
 * @ORM\Table(name="basket", options={"comment" = "корзина для товаров" }, indexes={@ORM\Index(name="IDX_BASKET_ORDER_ID_ANONIM_ID_ACTIVE", columns={"order_id","anonim_id","active"})})
 * @ORM\Entity(repositoryClass="App\BasketOrderBundle\Repository\BasketRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Basket extends BaseEntity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * ИД пользователя
     * @ORM\Column(name="anonim_id",type="string", options={"comment" = "ИД пользователя" })
     */
    protected $anonimId;

    /**
     * ИД заказа
     * @ORM\Column(name="order_id",type="string", nullable=true, options={"comment" = "ИД заказа" })
     */
    protected $orderId;

    /**
     * стоимость корзины до скидки
     * @SWG\Property(type="number", format="float", example=488.50)
     * @ORM\Column(name="price",type="decimal", precision=15, scale=2, options={"comment" = "стоимость корзины до скидки" })
     * @Type("string")
     */
    protected $price;

    /**
     * стоимость корзины со скидками
     * @SWG\Property(type="number", format="float", example=488.50)
     * @ORM\Column(name="cost",type="decimal", precision=15, scale=2, nullable=true, options={"comment" = "стоимость корзины со скидками" })
     * @Type("string")
     */
    protected $cost;

    /**
     * цена для расчета цены доставки
     * @SWG\Property(type="number", format="float", example=488.50)
     * @ORM\Column(name="cost_delivery", type="decimal", precision=15, scale=2, nullable=true, options={"comment" = "цена для расчета цены доставки" })
     * @Type("string")
     */
    protected $costDelivery; // todo: используется только до момента подтверждения заказа. затем - просто информационное

    /**
     * код валюты
     * @ORM\Column(name="currency",type="string", length=3, options={"comment" = "код валюты" })
     */
    protected $currency;

    /**
     * дата создания
     * @ORM\Column(name="created",type="datetime", options={"comment" = "дата создания" })
     */
    protected $created;

    /**
     * дата обновления
     * @ORM\Column(name="updated",type="datetime", options={"comment" = "дата обновления" })
     */
    protected $updated;

    /**
     * масса товаров
     * @SWG\Property(type="number", format="float", example=4.50)
     * @ORM\Column(name="weight",type="decimal", scale=3, nullable=true, options={"comment" = "масса товаров" })
     * @Type("string")
     */
    protected $weight;

    /**
     * статус корзины
     * @ORM\Column(name="active",type="boolean", options={"comment" = "статус корзины, активная - рабочая, неактивная-нерабочая" })
     */
    protected $active;

    /**
     * данные по карте из вирт кассы
     * @ORM\Column(name="card",type="text", nullable=true, options={"comment" = "данные по карте из вирт кассы" })
     */
    protected $card;

    /**
     * номер карты
     * @ORM\Column(name="card_num",type="string", nullable=true, options={"comment" = "номер карты" })
     */
    protected $cardNum;

    /**
     * флаг принудительного исключения номера карты из запроса в кассу
     * @ORM\Column(name="without_card", type="boolean", nullable=true, options={"comment" = "флаг принудительного исключения номера карты из запроса в кассу" })
     */
    protected $withoutCard;

    /**
     * номер карты партнера
     * @ORM\Column(name="card_num_partner", type="string", length=255, nullable=true, options={"comment" = "номер карты партнера" })
     */
    protected $cardNumPartner;

    /**
     * купоны к корзине
     * @Exclude()
     * @ORM\Column(name="coupons",type="text", nullable=true, options={"comment" = "купоны к корзине" })
     */
    protected $coupons;

    /**
     * пользовательский купон
     * @ORM\Column(name="coupon_user",type="string", nullable=true, options={"comment" = "пользовательский купон" })
     */
    protected $couponUser;

    /**
     * внутренний идентификатор для обмена с вирт кассой
     * @ORM\Column(name="identifier",type="string", length=50, nullable=true, options={"comment" = "внутренний идентификатор для обмена с вирт кассой" })
     */
    protected $identifier;

    /**
     * накопленные балы
     * @ORM\Column(name="points_for_earn",type="decimal", precision=9, scale=2, nullable=true, options={"comment" = "накопленные балы" })
     */
    protected $pointsForEarn;

    /**
     * @ORM\Column(name="points_for_spend",type="decimal", precision=15, scale=2, nullable=true)
     */
    protected $pointsForSpend;

    /**
     * @ORM\Column(name="signature",type="string", length=50, nullable=true)
     */
    protected $signature;

    /**
     * объем всех товаров в корзине
     * @SWG\Property(type="number", format="float", example=8.50)
     * @ORM\Column(name="volume",type="decimal", scale=2, nullable=true, options={"comment" = "объем всех товаров в корзине" })
     * @Type("string")
     */
    protected $volume;

    /**
     * идентификатор софт чека для отправки в рм
     * @ORM\Column(name="soft_cheque",type="string", length=12, nullable=true, options={"comment" = "идентификатор софт чека для отправки в рм" })
     */
    protected $softCheque;

    protected $itemsCount;

    private $orderObject;

    private $itemArray;

    private $errorMessage;

    private $storeId;

    /**
     * акции по корзине
     * @SWG\Property(property="actions", type="object", description="данные об акциях", ref=@Model(type=App\BasketOrderBundle\SwgModel\Actions::class))
     * @ORM\Column(name="actions",type="text", nullable=true, options={"comment" = "акции по корзине" })
     */
    protected $actions;

    /**
     * ответ от кассы
     * @ORM\Column(name="cashbox_response",type="text", nullable=true, options={"comment" = "ответ от кассы" })
     */
    protected $cashboxResponse;

    /**
     * Basket constructor.
     */
    function __construct()
    {
        $this->price = 0;
        $this->currency = 'RUR';
        $this->created = DateTimeHelper::getInstance()->getDateCurrent();
        $this->updated = DateTimeHelper::getInstance()->getDateCurrent();
        $this->weight = 0;
        $this->active = true;
    }

    /**
     * @ORM\PreFlush()
     */
    public function preFlush()
    {
//        $this->updated = DateTimeHelper::getInstance()->getDateCurrent();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price): self
    {
        $this->price = $price;

        return $this;
    }

    public function addPrice($price)
    {
        $this->price = $this->price + $price;

        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated(\DateTimeInterface $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    public function addWeight($weight)
    {
        $this->weight = $this->weight + $weight;

        return $this;
    }

    public function getAnonimId()
    {
        return $this->anonimId;
    }

    public function setAnonimId(string $anonimId)
    {
        $this->anonimId = $anonimId;
    }

    public function isActive()
    {
        return $this->active;
    }

    public function setActive(bool $active)
    {
        $this->active = $active;
    }

    public function getItemsCount()
    {
        return $this->itemsCount;
    }

    public function setItemsCount(int $itemsCount)
    {
        $this->itemsCount = $itemsCount;
    }

    public function getCard()
    {
        $items = json_decode($this->card, true);
        return $items;
    }

    public function setWithoutCard(bool $withoutCard)
    {
        $this->withoutCard = $withoutCard;

        return $this;
    }

    public function getWithoutCard()
    {
        return $this->withoutCard;
    }

    /**
     * @param $card
     */
    public function addCard($card)
    {
        if ($this->card) {
            $items = json_decode($this->card, true);
            if (!array_key_exists($card, $items)) {
                $items[$card] = ['number' => $card];
                $this->card = json_encode($items, JSON_UNESCAPED_UNICODE);
            }
        } else {
            $this->card = json_encode([$card => ['number' => $card]], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * @param $card
     */
    public function setCard($card = null)
    {
        if ($card === null) {
            $this->card = null;
        } else {
            if (is_array($card) and count($card) > 0) {
                $this->card = json_encode($card, JSON_UNESCAPED_UNICODE);
            } else {
                $this->card = null;
            }
        }
    }

    /**
     * @return mixed|null
     */
    public function getCoupons()
    {
        if ($this->coupons) {

            return json_decode($this->coupons, true);
        }

        return null;
    }

    public function setCoupons(?array $coupons)
    {
        $this->coupons = $coupons ? json_encode($coupons, JSON_UNESCAPED_UNICODE) : null;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public function getPointsForEarn()
    {
        return $this->pointsForEarn;
    }

    public function setPointsForEarn($pointsForEarn)
    {
        $this->pointsForEarn = $pointsForEarn;
    }

    public function getPointsForSpend()
    {
        return $this->pointsForSpend;
    }

    public function setPointsForSpend($pointsForSpend)
    {
        $this->pointsForSpend = $pointsForSpend;
    }

    public function getSignature()
    {
        return $this->signature;
    }

    public function setSignature(string $signature)
    {
        $this->signature = $signature;
    }

    public function getCost()
    {
        return $this->cost;
    }

    public function setCost($cost)
    {
        $this->cost = $cost;
    }

    public function addCost($cost)
    {
        $this->cost = $this->cost + $cost;
    }

    public function getCardNum()
    {
        return $this->cardNum;
    }

    public function setCardNum($cardNum)
    {
        $this->cardNum = $cardNum;
    }

    /**
     * @param $card
     */
    public function setClearCard($card)
    {
        if ($card) {
            if ($card == 'clear') {
                $this->setCardNum(null);
                $this->setCard();
            } else {
                $cardNum = $this->getCardNum();
                if ($cardNum != $card) {
                    $this->setCardNum($card);
                    $this->setCard();
                }
            }
        }
    }

    /**
     * @param array $items
     * @param array $costDeliveryExcludedDiscountCodes
     */
    public function updateBasketPrice($items = [], $costDeliveryExcludedDiscountCodes = [])
    {
        $sPrice = 0;
        $sCost = 0;
        $sVolume = 0;
        $sWeight = 0;
        $excludedDiscountSum = 0;
        /** @var Item $item */
        foreach ($items as $item) {
            if (is_array($item)) {
                $quantity = isset($item['quantity']) ? $item['quantity'] : null;
                $price = isset($item['price']) ? $item['price'] : null;
                $cost = isset($item['cost']) ? $item['cost'] : null;
                $volume = isset($item['volume']) ? $item['volume'] : null;
                $weight = isset($item['weight']) ? $item['weight'] : null;
                $discounts = isset($item['discounts']) ? $item['discounts'] : null;
            } else {
                $quantity = $item->getQuantity();
                $price = $item->getPrice();
                $cost = $item->getCost();
                $volume = $item->getVolume();
                $weight = $item->getWeight();
                $discounts = $item->getDiscounts();
            }
            $sPrice += ($price * $quantity);
            $sCost += $cost ? $cost : ($price * $quantity);
            $sVolume += ($volume * $quantity);
            $sWeight += ($weight * $quantity);
            if(is_array($discounts)) {
                foreach ($discounts as $discount) {
                    if(isset($discount['discountType']) && isset($discount['discountAmount'])) {
                        if (array_search($discount['discountType'], $costDeliveryExcludedDiscountCodes) !== false) {
                            $excludedDiscountSum += $discount['discountAmount'];
                        }
                    }
                }
            }

        }
        $this->setWeight($sWeight );
        $this->setPrice($sPrice);
        $this->setCost($sCost);
        $this->setCostDelivery($sCost + $excludedDiscountSum);
        $this->setVolume($sVolume );
    }

    /**
     * @param $coupon
     */
    public function _addCoupon($coupon, $type = null)
    {
        $name = ShopConst::findCouponOnlineTitle($coupon);
        $items = $this->getCoupons();
        if ($items) {
            if (is_array($coupon)) {
                $number = $coupon['number'];
                if (!array_key_exists($number, $items)) {
                    if ($name !== null) {
                        $coupon['name'] = $name;
                    }
                    if ($type !== null) {
                        $coupon['type'] = $type;
                    }
                    $items[$number] = ['number' => $coupon];
                    $this->setCoupons($items);
                } else {
                    unset($items[$number]);
                    if ($name !== null) {
                        $coupon['name'] = $name;
                    }
                    if ($type !== null) {
                        $coupon['type'] = $type;
                    }
                    $items[$number] = ['number' => $coupon];
                    $this->setCoupons($items);
                }
            } else {
                if (!array_key_exists($coupon, $items)) {
                    if ($name !== null) {
                        $cpn = [
                            'name' => $name,
                            'number' => $coupon,
                        ];
                    } else {
                        $cpn = ['number' => $coupon];
                    }
                    if ($type !== null) {
                        $cpn['type'] = $type;
                    }
                    $items[$coupon] = $cpn;
                    $this->setCoupons($items);
                }
            }
        } else {
            if ($name !== null) {
                $cpn = [
                    'name' => $name,
                    'number' => $coupon,
                ];
            } else {
                $cpn = [
                    'number' => $coupon,
                ];
            }
            $items[$coupon] = $cpn;
            $this->setCoupons($items);
        }
    }

    /**
     * @param $number
     * @param null $type
     */
    public function addCoupon($number, $type = null)
    {
        $items = $this->getCoupons() ? $this->getCoupons() : [];
        if ($type) {
            if (array_key_exists($number, $items)) {
                $name = ShopConst::findCouponOnlineTitle($number);
                $name ? $items[$number]['name'] = $name : null;
                $number ? $items[$number]['number'] = $number : null;
                $type ? $items[$number]['type'] = $type : null;
                $this->setCoupons($items);
            }
        } else {
            $name = ShopConst::findCouponOnlineTitle($number);
            $name ? $items[$number]['name'] = $name : null;
            $number ? $items[$number]['number'] = $number : null;
            $type ? $items[$number]['type'] = $type : null;
            $this->setCoupons($items);
        }
    }

    /**
     * @param string|null $couponNumber
     * @return string|null
     */
    public function prepareCouponNumber(?string $couponNumber): ?string
    {
        return $couponNumber ? mb_convert_case($couponNumber, MB_CASE_UPPER, 'UTF-8') : null;
    }


    /**
     * @return string
     */
    public function getCouponsNumberString()
    {
        $out = '';
        $coupons = $this->getCoupons();
        if ($coupons) {
            foreach ($coupons as $key => $coupon) {
                $out .= '-' . $key;
            }
        }

        return $out;
    }

    /**
     * @param $coupon
     */
    public function addCashboxCoupon($coupon)
    {
        if ($this->coupons) {
            $items = json_decode($this->coupons, true);
            $number = $coupon['number'];
            if (array_key_exists($number, $items)) {
                $items[$number] = $coupon;
                $this->coupons = json_encode($items, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    /**
     * @param $output
     */
    public function setOutCashbox($output)
    {
        $cardNum = $this->getCardNum();
        $card = isset($output['card']) ? $output['card'] : null;
        $cardNumber = isset($card['number']) ? $card['number'] : null;
        if ($cardNum == $cardNumber) {
            $this->setCard($card);
        }

        $coupons = isset($output['coupons']) ? $output['coupons'] : null;
        if ($coupons and is_array($coupons)) {
            foreach ($coupons as $coupon) {
                $number = isset($coupon['number']) ? $coupon['number'] : null;
                $type = isset($coupon['type']) ? $coupon['type'] : null;
                $this->addCoupon($number, $type);
            }
        }
        (isset($output['cost']) and $output['cost']) ? $this->setCost($output['cost']) : $this->setCost(0);
        (isset($output['identifier']) and $output['identifier']) ? $this->setIdentifier($output['identifier']) : $this->setIdentifier('');
        (isset($output['pointsForEarn']) and $output['pointsForEarn']) ? $this->setPointsForEarn($output['pointsForEarn']) : $this->setPointsForEarn(0);
        (isset($output['pointsForSpend']) and $output['pointsForSpend']) ? $this->setPointsForSpend($output['pointsForSpend']) : $this->setPointsForSpend(0);
        (isset($output['signature']) and $output['signature']) ? $this->setSignature($output['signature']) : $this->setSignature('');

        (isset($output['oldcost']) and $output['oldcost']) ? $this->setPrice($output['oldcost']) : null;
    }

    public function getVolume()
    {
        return $this->volume;
    }

    public function setVolume($volume)
    {
        $this->volume = $volume;
    }

    public function addVolume($volume)
    {
        $this->volume = $this->volume + $volume;
    }

    /**
     * @return Order
     */
    public function getOrderObject()
    {
        return $this->orderObject;
    }

    /**
     * @param Order $orderObject
     */
    public function setOrderObject($orderObject)
    {
        $this->orderObject = $orderObject;
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
     *
     */
    public function clearBasket()
    {
        $this->setWeight(0);
        $this->setPrice(0);
        $this->setCost(0);
        $this->setCostDelivery(0);
        $this->setVolume(0);
        $this->setPointsForEarn(0);
        $this->setPointsForSpend(0);
        $this->setSignature('');
        $this->setSoftCheque('');
        $this->setCashboxResponse('');
        $this->setCoupons(null);
        $this->setCouponUser(null);
        $this->setActions(null);
    }

    /**
     * @return mixed
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @param mixed $errorMessage
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    public function getCouponsJson()
    {
        if ($this->coupons) {
            return $this->coupons;
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getSoftCheque()
    {
        return $this->softCheque;
    }

    /**
     * @param mixed $softCheque
     */
    public function setSoftCheque($softCheque)
    {
        $this->softCheque = $softCheque;
    }

    /**
     * @return string
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param string $storeId
     * @return $this
     */
    public function setStoreId(string $storeId)
    {
        $this->storeId = $storeId;

        return $this;
    }

    /**
     * @param string $card
     * @return false|int
     */
    public function checkCard(string $card) {
        return preg_match('/^(27\d{11})$/', $card);
    }

    /**
     * @return mixed
     */
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

    public function setDigitalRound()
    {
        $this->setPrice(AppHelper::getDigitalRound($this->getPrice()));
        $this->setCost(AppHelper::getDigitalRound($this->getCost()));
        $this->setWeight(AppHelper::getDigitalRound($this->getWeight()));
        $this->setVolume(AppHelper::getDigitalRound($this->getVolume()));
    }

    public function getCostDelivery(): ?string
    {
        return $this->costDelivery;
    }

    public function setCostDelivery(?string $costDelivery): self
    {
        $this->costDelivery = $costDelivery;

        return $this;
    }

    public function getCardNumPartner(): ?string
    {
        return $this->cardNumPartner;
    }

    public function setCardNumPartner(?string $cardNumPartner): self
    {
        $this->cardNumPartner = $cardNumPartner;

        return $this;
    }

    public function getCashboxResponse()
    {
        return $this->cashboxResponse;
    }

    public function setCashboxResponse(string $cashboxResponse)
    {
        $this->cashboxResponse = $cashboxResponse;
    }

    public function getCouponUser(): ?string
    {
        return $this->couponUser;
    }

    public function setCouponUser(?string $couponUser): self
    {
        $this->couponUser = $couponUser;

        return $this;
    }

}