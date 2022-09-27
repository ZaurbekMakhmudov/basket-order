<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 21.07.19
 * Time: 20:36
 */

namespace App\BasketOrderBundle\Era;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class App\BasketOrderBundle\Era\EshopOrderPosition
 * @ORM\Table(name="eshop_order_positions")
 * @ORM\Entity(repositoryClass="App\BasketOrderBundle\Repository\EshopOrderPositionRepository")
 * @package App\BasketOrderBundle\Era
 */
class EshopOrderPosition
{

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    private $order_id;
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $product_id;
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $product_name;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $product_amount;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $product_unit_price;
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $product_ean;
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $product_discount;
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $processed_by_era_date;
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $processed_by_eshop_date;
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $packet_id;
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $product_pricelist_id;
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $coupon;
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $bonus_earn;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $sto_good;


    function __construct()
    {
    }
    public function serialize($data)
    {
        foreach ($data as $key=>$value){
            $this->{$key} = $value;
        }
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @param $order_id
     */
    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;
    }

    /**
     * @return string
     */
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * @param $product_id
     */
    public function setProductId($product_id)
    {
        $this->product_id = $product_id;
    }

    /**
     * @return string
     */
    public function getProductName()
    {
        return $this->product_name;
    }

    /**
     * @param $product_name
     */
    public function setProductName($product_name)
    {
        $this->product_name = $product_name;
    }

    public function getProductAmount()
    {
        return $this->product_amount;
    }

    /**
     * @param $product_amount
     */
    public function setProductAmount($product_amount)
    {
        $this->product_amount = $product_amount;
    }

    public function getProductUnitPrice()
    {
        return $this->product_unit_price;
    }

    /**
     * @param $product_unit_price
     */
    public function setProductUnitPrice($product_unit_price)
    {
        $this->product_unit_price = $product_unit_price;
    }

    /**
     * @return string
     */
    public function getProductEan()
    {
        return $this->product_ean;
    }

    /**
     * @param $product_ean
     */
    public function setProductEan($product_ean)
    {
        $this->product_ean = $product_ean;
    }

    /**
     * @return string
     */
    public function getProductDiscount()
    {
        return $this->product_discount;
    }

    /**
     * @param $product_discount
     */
    public function setProductDiscount($product_discount)
    {
        $this->product_discount = $product_discount;
    }

    /**
     * @return
     */
    public function getProcessedByEraDate()
    {
        return $this->processed_by_era_date;
    }

    /**
     * @param  $processed_by_era_date
     */
    public function setProcessedByEraDate($processed_by_era_date)
    {
        $this->processed_by_era_date = $processed_by_era_date;
    }

    /**
     * @return
     */
    public function getProcessedByEshopDate()
    {
        return $this->processed_by_eshop_date;
    }

    /**
     * @param  $processed_by_eshop_date
     */
    public function setProcessedByEshopDate($processed_by_eshop_date)
    {
        $this->processed_by_eshop_date = $processed_by_eshop_date;
    }

    /**
     * @return string
     */
    public function getPacketId()
    {
        return $this->packet_id;
    }

    /**
     * @param $packet_id
     */
    public function setPacketId($packet_id)
    {
        $this->packet_id = $packet_id;
    }

    /**
     * @return string
     */
    public function getProductPricelistId()
    {
        return $this->product_pricelist_id;
    }

    /**
     * @param $product_pricelist_id
     */
    public function setProductPricelistId($product_pricelist_id)
    {
        $this->product_pricelist_id = $product_pricelist_id;
    }

    /**
     * @return string
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @param $coupon
     */
    public function setCoupon($coupon)
    {
        $this->coupon = $coupon;
    }

    /**
     * @return string
     */
    public function getBonusEarn()
    {
        $items = json_decode($this->bonus_earn, true);

        return $items;
    }

    /**
     * @param $bonus_earn
     */
    public function setBonusEarn($bonus_earn)
    {
        $this->bonus_earn = json_encode($bonus_earn, JSON_UNESCAPED_UNICODE);
    }

    public function getStoGood()
    {
        return $this->sto_good;
    }

    /**
     * @param $sto_good
     */
    public function setStoGood($sto_good)
    {
        $this->sto_good = $sto_good;
    }

}