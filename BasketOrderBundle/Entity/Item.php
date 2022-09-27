<?php

namespace App\BasketOrderBundle\Entity;

use App\BasketOrderBundle\Helper\AppHelper;
use App\BasketOrderBundle\Model\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Type;

/**
 * @ORM\Table(name="item", options={"comment" = "позиция товара в корзине" }, indexes={@ORM\Index(name="IDX_ITEM_BASKET_ID", columns={"basket_id"})})
 * @ORM\Entity(repositoryClass="App\BasketOrderBundle\Repository\ItemRepository")
 * Serializer\ExclusionPolicy("NONE")
 */
class Item extends BaseEntity
{
    /**
     * @var integer
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(name="name",type="string", options={"comment" = "название товара" })
     */
    protected $name;

    /**
     * @SWG\Property(type="number", format="float", example=488.50)
     * @ORM\Column(name="price",type="decimal", precision=15, scale=2, options={"comment" = "цена позицции" })
     * @Type("string")
     */
    protected $price;
    /**
     * стоимость позиции после скидки
     * @SWG\Property(type="number", format="float", example=488.50)
     * @ORM\Column(name="cost",type="decimal", precision=15, scale=2, nullable=true, options={"comment" = "стоимость позиции с учетом количества и скидки" })
     * @Type("string")
     */
    protected $cost;
    /**
     * @SWG\Property(type="number", format="float", example=488.50)
     * @ORM\Column(name="cost_one_unit",type="decimal", precision=15, scale=2, nullable=true, options={"comment" = "стоимость позиции с учетом количества и скидки" })
     * @Type("string")
     */
    protected $costOneUnit;
    /**
     * бывший sumWithoutDiscounts - стоимость позиции до скидки (по сути price * count)
     * @SWG\Property(type="number", format="float", example=488.50)
     * @ORM\Column(name="old_cost",type="decimal", precision=15, scale=2, nullable=true, options={"comment" = "стоимость позиции до скидки, устарело" })
     * @Type("string")
     */
    protected $oldCost;
    /**
     * "minprice": 0,
     * @SWG\Property(type="number", format="float", example=488.50)
     * @ORM\Column(name="min_price",type="decimal", precision=15, scale=2, nullable=true, options={"comment" = "минмальная цена" })
     * @Type("string")
     */
    protected $minPrice;
    /**
     * @ORM\Column(name="original_quantity", type="integer", nullable=true, options={"comment" = "количество товара исходное" })
     */
    protected $originalQuantity;
    /**
     * @ORM\Column(name="quantity",type="integer", options={"comment" = "количество товара" })
     */
    protected $quantity;
    /**
     * @ORM\Column(name="basket_id",type="integer", options={"comment" = "ИД корзины" })
     */
    protected $basketId;
    /**
     * @ORM\Column(name="article",type="string", length=50, options={"comment" = "ИД товара, артикул" })
     */
    protected $article;
    /**
     * @ORM\Column(name="product_image_url",type="string", length=255, nullable=true, options={"comment" = "ссылка на картинку к товару" })
     */
    protected $productImageUrl;
    /**
     * @SWG\Property(type="number", format="float", example=0.50)
     * @ORM\Column(name="weight",type="decimal", scale=3, options={"comment" = "масса единицы товара" })
     * @Type("string")
     */
    protected $weight;

    /**
     * @ORM\Column(name="discount",type="string", nullable=true, options={"comment" = "скидка по позиции с учетом количества" })
     */
    protected $discount;
    /**
     * @ORM\Column(name="bonus",type="decimal", precision=9, scale=2, nullable=true, options={"comment" = "бонусы" })
     */
    protected $bonus;
    /**
     * "campaigncode": 2069581681,
     * "campaignname": "15% для ИМ",
     * "discountcode": 1184868550,
     * "discountmode": 9,
     * "discountname": "15% для ИМ",
     * "discountrate": 0,
     * "discountsum": 12,
     * "discounttype": 2,
     * "ispositiondiscount": 1,
     * "minpriceignored": true,
     * "posnum": 1
     *
     * @var array
     * @ORM\Column(name="discounts",type="text", nullable=true, options={"comment" = "данные по скидке " })
     */
    protected $discounts;

    /**
     * @ORM\Column(name="measure",type="decimal", scale=3, nullable=true, options={"comment" = "количесвто едици измерения " })
     */
    protected $measure;
    /**
     * @ORM\Column(name="measure_name",type="string", length=5, nullable=true, options={"comment" = "наименование единицы измерения" })
     */
    protected $measureName;
    /**
     * @ORM\Column(name="payment_method",type="integer", nullable=true, options={"comment" = "метод оплаты" })
     */
    protected $paymentMethod;
    /**
     * @ORM\Column(name="payment_object",type="integer", nullable=true)
     */
    protected $paymentObject;
    /**
     * @ORM\Column(name="pos_num",type="integer", nullable=true)
     */
    protected $posNum;
    /**
     * @ORM\Column(name="tara_mode",type="integer", nullable=true)
     */
    protected $taraMode;
    /**
     * @ORM\Column(name="vat_code",type="integer", nullable=true)
     */
    protected $vatCode;
    /**
     * @ORM\Column(name="vat_rate",type="integer", nullable=true)
     */
    protected $vatRate;
    /**
     * @ORM\Column(name="vat_sum",type="decimal", precision=15, scale=2, nullable=true)
     */
    protected $vatSum;
    /**
     * @SWG\Property(type="string", example=" [{'ispositionbonus': 1,'amount': 45,'campaignname': 'Базовые баллы 2.0','cardnumber': 2775863200307}]",
     *     description="выводит массив бонусов для указанной позиции, до версии 1, начиная с в2 выводится поле bonuses")
     * @ORM\Column(name="earned_bonuses",type="json_array", nullable=true, options={"comment" = "полученные бонусы" })
     * @Serializer\Until("1")
     */
    protected $earnedBonuses;
    /**
     * @ORM\Column(name="barcode",type="string", length=50, nullable=true, options={"comment" = "штрихкод" })
     */
    protected $barcode;
    /**
     * @ORM\Column(name="dept",type="string", length=50, nullable=true)
     */
    protected $dept;

    /**
     * @SWG\Property(type="number", format="float", example=8.50)
     * @ORM\Column(name="volume",type="decimal", scale=2, nullable=true, options={"comment" = "объем единицы товара" })
     * @Type("string")
     */
    protected $volume;

    /**
     * @SWG\Property(type="string", example="45", description="выводит сумму бонусов для указанной позиции, начиная с версии 2")
     * @Serializer\Accessor(getter="getAmounts")
     * @Serializer\Since("2")
     */
    private $bonuses;

    /**
     * @SWG\Property(property="actions", type="object", description="данные об акциях", ref=@Model(type=App\BasketOrderBundle\SwgModel\Actions::class))
     * @ORM\Column(type="text", nullable=true)
     */
    protected $actions;

    /**
     * @SWG\Property(type="string", example="shoes")
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $tmctype = null;

    /**
     * @SWG\Property(type="string", example="23123")
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $excisemark = null;


    function __construct()
    {
        $this->weight = 0;
        $this->volume = 0;
    }

    public function getId()
    {
        return $this->id;
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

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setQuantity($quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function addQuantity($quantity): self
    {
        $this->quantity = $this->quantity + $quantity;

        return $this;
    }

    public function getBasketId(): ?int
    {
        return $this->basketId;
    }

    public function setBasketId(int $basketId): self
    {
        $this->basketId = $basketId;

        return $this;
    }

    public function getProductImageUrl()
    {
        return $this->productImageUrl;
    }

    public function setProductImageUrl(string $productImageUrl)
    {
        $this->productImageUrl = $productImageUrl;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    public function getDiscount()
    {
        return $this->discount;
    }

    public function setDiscount($discount)
    {
        $this->discount = $discount;
    }

    public function getBonus()
    {
        return $this->bonus;
    }

    public function setBonus($bonus)
    {
        $this->bonus = $bonus;
    }

    public function getArticle()
    {
        return $this->article;
    }

    public function setArticle(string $article)
    {
        $this->article = $article;
    }

    public function getDiscounts()
    {
        $items = json_decode($this->discounts, true);

        return $items;
    }

    public function setDiscounts($discounts)
    {
        $this->discounts = json_encode($discounts, JSON_UNESCAPED_UNICODE);
    }

    public function getMeasure()
    {
        return $this->measure;
    }

    public function setMeasure(int $measure)
    {
        $this->measure = $measure;
    }

    public function getMeasureName()
    {
        return $this->measureName;
    }

    public function setMeasureName(string $measureName)
    {
        $this->measureName = $measureName;
    }

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(int $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getPaymentObject()
    {
        return $this->paymentObject;
    }

    public function setPaymentObject(int $paymentObject)
    {
        $this->paymentObject = $paymentObject;
    }

    public function getPosNum()
    {
        return $this->posNum;
    }

    public function setPosNum(int $posNum)
    {
        $this->posNum = $posNum;
    }

    public function getTaraMode()
    {
        return $this->taraMode;
    }

    public function setTaraMode(int $taraMode)
    {
        $this->taraMode = $taraMode;
    }

    public function getVatCode()
    {
        return $this->vatCode;
    }

    public function setVatCode(int $vatCode)
    {
        $this->vatCode = $vatCode;
    }

    public function getVatRate()
    {
        return $this->vatRate;
    }

    public function setVatRate(int $vatRate)
    {
        $this->vatRate = $vatRate;
    }

    public function getVatSum()
    {
        return $this->vatSum;
    }

    public function setVatSum($vatSum)
    {
        $this->vatSum = $vatSum;
    }

    /**
     * @param bool $getCostForced
     * @return mixed
     */
    public function getCost($getCostForced = false)
    {
        // @todo IT-58 проверить везде возможность передать true в параметре getCostForced. Если возможно то передать и удалить этот комментарий
        if($getCostForced) {

            return $this->cost;
        }
        if ($this->cost) {

            return $this->cost;
        } else {

            return $this->price * $this->quantity;
        }
    }

    /**
     * @param $cost
     */
    public function setCost($cost)
    {
        $this->cost = $cost;
        if ($this->getQuantity() > 1) {
            $this->setCostOneUnit($this->cost / $this->getQuantity());
        } else {
            $this->setCostOneUnit($cost);
        }
    }
    public function getCostOneUnit()
    {
        return $this->costOneUnit;
    }
    public function setCostOneUnit($costOneUnit)
    {
        $this->costOneUnit = $costOneUnit;
    }

    public function getEarnedBonuses()
    {
        return $this->earnedBonuses;
    }

    public function setEarnedBonuses($earnedBonuses)
    {
        $this->earnedBonuses = $earnedBonuses;
    }

    /**
     * @param $earnedBonuse
     */
    public function addEarnedBonuse($earnedBonuse)
    {
        if ($this->earnedBonuses) {
            $items = $this->earnedBonuses;
            if (!array_key_exists($earnedBonuse, $items)) {
                $items[$earnedBonuse] = ['cardnumber' => $earnedBonuse];
                $this->earnedBonuses = $items;
            }
        } else {
            $this->earnedBonuses = [$earnedBonuse => ['cardnumber' => $earnedBonuse]];
        }
    }

    /**
     * @param $earnedBonuses
     * @return int|mixed
     */
    private function getBonusesFromEarnedBonuses($earnedBonuses)
    {
        $bonuses = 0;
        if (!empty($earnedBonuses) && is_array($earnedBonuses)) {
            foreach ($earnedBonuses as $item) {
                $bonuses += $item['amount'] ?? 0;
            }
        }

        return $bonuses;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getOldCost()
    {
        return $this->oldCost;
    }

    public function setOldCost($oldCost)
    {
        $this->oldCost = $oldCost;
    }

    public function getMinPrice()
    {
        return $this->minPrice;
    }

    public function setMinPrice($minPrice)
    {
        $this->minPrice = $minPrice;
    }

    public function getBarcode()
    {
        return $this->barcode;
    }

    public function setBarcode(string $barcode)
    {
        $this->barcode = $barcode;
    }

    public function getDept()
    {
        return $this->dept;
    }

    public function setDept(string $dept)
    {
        $this->dept = $dept;
    }

    public function getOriginalQuantity()
    {
        return $this->originalQuantity;
    }

    public function setOriginalQuantity($originalQuantity): self
    {
        $this->originalQuantity = $originalQuantity;

        return $this;
    }

    /**
     * @param $oItem
     */
    public function setItemCashbox($oItem)
    {
        $name = $this->getName();
        if (!$name) {
            $name = isset($oItem["name"]) ? $oItem["name"] : '';
            $this->setName($name);
        }

        $barcode = $this->getBarcode();
        if (!$barcode) {
            $barcode = isset($oItem["barcode"]) ? $oItem["barcode"] : '';
            $this->setBarcode($barcode);
        }
        $article = $this->getArticle();
        if (!$article) {
            $article = isset($oItem["article"]) ? $oItem["article"] : '';
            $this->setArticle($article);
        }

        $dept = isset($oItem['dept']) ? $oItem['dept'] : 0;
        $earnedbonuses = isset($oItem['earnedbonuses']) ? $oItem['earnedbonuses'] : [];
        $discounts = isset($oItem['discounts']) ? $oItem['discounts'] : [];
        $measure = isset($oItem["measure"]) ? $oItem["measure"] : 0;
        $measurename = isset($oItem["measurename"]) ? $oItem["measurename"] : '';
        $minprice = isset($oItem["minprice"]) ? $oItem["minprice"] : 0;
        $paymentmethod = isset($oItem["paymentmethod"]) ? $oItem["paymentmethod"] : '';
        $paymentobject = isset($oItem["paymentobject"]) ? $oItem["paymentobject"] : '';
        $posnum = isset($oItem["posnum"]) ? $oItem["posnum"] : 0;
        $price = isset($oItem["price"]) ? $oItem["price"] : 0;
        $quantity = isset($oItem["quantity"]) ? $oItem["quantity"] : 0;
        $cost = isset($oItem["cost"]) ? $oItem["cost"] : 0;
        $oldcost = isset($oItem["oldcost"]) ? $oItem["oldcost"] : 0;
        $taramode = isset($oItem["taramode"]) ? $oItem["taramode"] : 0;
        $vatcode = isset($oItem["vatcode"]) ? $oItem["vatcode"] : 0;
        $vatrate = isset($oItem["vatrate"]) ? $oItem["vatrate"] : 0;
        $vatsum = isset($oItem["vatsum"]) ? $oItem["vatsum"] : 0;

        $this->setDept($dept);
        $this->setEarnedBonuses($earnedbonuses);
        $this->setBonus($this->getBonusesFromEarnedBonuses($earnedbonuses));
        $this->setDiscounts($discounts);
        $this->setMeasure($measure);
        $this->setMeasureName($measurename);
        $this->setMinPrice($minprice);
        $this->setPaymentMethod($paymentmethod);
        $this->setPaymentObject($paymentobject);
        $this->setPosNum($posnum);
        $this->setPrice($price);
        $this->setQuantity($quantity);
        $this->setCost($cost);
        $this->setOldCost($oldcost);
        $this->setTaraMode($taramode);
        $this->setVatCode($vatcode);
        $this->setVatRate($vatrate);
        $this->setVatSum($vatsum);
        $this->setDiscountSum();
    }

    public function getVolume()
    {
        return $this->volume;
    }

    public function setVolume($volume)
    {
        $this->volume = $volume;
    }

    /**
     * @param $items
     */
    public function addDiscount($items)
    {
        $discount = 0;
        if ($items and is_array($items)) {
            foreach ($items as $item) {
                $discountsum = (isset($item['discountsum']) and $item['discountsum']) ? $item['discountsum'] : null;
                $discount = $discountsum ? $discount + $discountsum : 0;
            }
        }
        $this->discount = $discount;
    }

    /**
     * @return mixed
     */
    public function getDiscountSum()
    {
        $discount = $this->getDiscount();
        if (!$discount) {
            $items = $this->getDiscounts();
            $this->addDiscount($items);
        }

        return $this->discount;
    }

    /**
     * @param $itemData
     */
    public function addItem($itemData, $issetDiscounts = false)
    {
        isset($itemData['name']) ? $this->setName($itemData['name']) : $this->setName('');
        isset($itemData['price']) ? $this->setPrice($itemData['price']) : $this->setPrice(0);
        $this->setMinPrice(0);
        isset($itemData['quantity']) ? $this->setQuantity($itemData['quantity']) : $this->setQuantity(0);
        isset($itemData['weight']) ? $this->setWeight($itemData['weight']) : $this->setWeight(0);
        isset($itemData['volume']) ? $this->setVolume($itemData['volume']) : $this->setVolume(0);
        isset($issetDiscounts) && isset($itemData['discounts']) ? $this->setDiscounts($itemData['discounts']): null;
        if(isset($itemData['discounts'])) {
            if($issetDiscounts && count($itemData['discounts']) > 0) {
                $discountSum = 0;
                foreach ($itemData['discounts'] as $discount) {
                    $discountSum += intval($discount['discountAmount']);
                }
                $this->setDiscount($discountSum * $itemData['quantity']);
            }
        }
        isset($itemData['article']) ? $this->setArticle($itemData['article']) : $this->setArticle('');
        isset($itemData['barcode']) ? $this->setBarcode($itemData['barcode']) : $this->setBarcode('');
        isset($itemData['product_image_url']) ? $this->setProductImageUrl($itemData['product_image_url']) : null;
        isset($itemData['cost']) ? $this->setCost($itemData['cost']) : $this->setCost(0);
        isset($itemData['label_type']) ? $this->setTmctype($itemData['label_type']) : $this->setTmctype(null);
        isset($itemData['lables']) ? $this->setExcisemark($itemData['lables']) : $this->setExcisemark(null);
    }

    /**
     * @return int
     */
    public function getAmounts()
    {
        $amounts = 0;
        $earnedBonuses = $this->getEarnedBonuses();
        if ($earnedBonuses) {
            foreach ($earnedBonuses as $earnedBonus) {
                $amount = $earnedBonus['amount'];
                $amounts = $amounts + $amount;
            }
        }

        return $amounts;
    }

    /**
     *
     */
    protected function setDiscountSum()
    {
        $discountSum = 0;
        $discounts = $this->getDiscounts();
        if ($discounts) {
            foreach ($discounts as $discount) {
                $dSum = isset($discount['discountsum']) ? $discount['discountsum'] : null;
                if ($dSum) {
                    $discountSum = $discountSum + $dSum;
                }
            }
        }

        $discountSum ? $this->discount = $discountSum : null;
    }

    /**
     * "discounts": "[{\"posnum\":1,\"ispositiondiscount\":1,\"discountcode\":18856,\"discountmode\":9,\"discounttype\":2,
     * \"discountrate\":0,\"discountname\":\"5303 2772999011814\",\"discountsum\":34,\"campaigncode\":1316868969,\"campaignname\":\"5303 2772999011814\",\"minpriceignored\":false}]",
     */
    public function getDiscountName()
    {
        $dDiscountNames = [];
        $discounts = $this->getDiscounts();
        if ($discounts) {
            foreach ($discounts as $discount) {
                isset($discount['discountname']) ? $dDiscountNames[] = $discount['discountname'] : null;
            }
        }

        return ($dDiscountNames ? $dDiscountName = implode(';', $dDiscountNames) : null);
    }

    /**
     * @return null|string
     */
    public function getDiscountCode()
    {
        $dDiscountCodes = [];
        $discounts = $this->getDiscounts();
        if ($discounts) {
            foreach ($discounts as $discount) {
                isset($discount['discountcode']) ? $dDiscountCodes[] = $discount['discountcode'] : null;
            }
        }

        return ($dDiscountCodes ? $dDiscountCode = implode(';', $dDiscountCodes) : null);
    }

    public function getDiscountsJsonString()
    {

        return $this->discounts;
    }
    public function setDigitalRound()
    {
        $this->setPrice(AppHelper::getDigitalRound($this->getPrice()));
        $this->setCost(AppHelper::getDigitalRound($this->getCost()));
        $this->setCostOneUnit(AppHelper::getDigitalRound($this->getCostOneUnit()));
        $this->setOldCost(AppHelper::getDigitalRound($this->getOldCost()));
        $this->setVolume(AppHelper::getDigitalRound($this->getVolume()));
        $this->setWeight(AppHelper::getDigitalRound($this->getWeight()));
        $this->setMinPrice(AppHelper::getDigitalRound($this->getMinPrice()));
        $this->setDiscount(AppHelper::getDigitalRound($this->getDiscount()));
    }

    /**
     * @return array|null
     */
    public function getActions() : ?array
    {
        return json_decode($this->actions, true);
    }

    /**
     * @param array $actions
     */
    public function setActions(array $actions)
    {
        $this->actions = json_encode($actions, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array|null
     */
    public function getExcisemark(): ?array
    {
        if($this->excisemark) {

            return json_decode($this->excisemark, true);
        }

        return null;
    }

    /**
     * @param array|null $excisemark
     */
    public function setExcisemark(?array $excisemark): void
    {
        if($excisemark) {
            $this->excisemark = json_encode($excisemark);
        }
    }

    /**
     * @return string|null
     */
    public function getTmctype(): ?string
    {
        return $this->tmctype;
    }

    /**
     * @param string|null $tmctype
     */
    public function setTmctype(?string $tmctype): void
    {
        $this->tmctype = $tmctype;
    }
}