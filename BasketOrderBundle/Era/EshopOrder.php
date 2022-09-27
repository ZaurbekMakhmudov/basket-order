<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 21.07.19
 * Time: 20:35
 */

namespace App\BasketOrderBundle\Era;

use App\BasketOrderBundle\Model\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class App\BasketOrderBundle\Era\EshopOrder
 * @ORM\Table(name="eshop_orders")
 * @ORM\Entity(repositoryClass="App\BasketOrderBundle\Repository\EshopOrderRepository")
 * @package App\BasketOrderBundle\Era
 */
class EshopOrder extends BaseEntity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_id;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_status;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_sum;
    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $order_date;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_payment_type;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $product_pricelist_id;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $product_pricelist_param;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_delivery_type;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_customer_name;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_customer_phone;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_customer_email;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_source_identifier;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_logagent_name;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_logagent_phone;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_logagent_email;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_delivery_address;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $user_dcard_id;
    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $order_delivery_logagent_date;
    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $order_delivery_customer_date;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_deliverypoint_name;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_deliverypoint_phone;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_deliverypoint_email;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_deliverypoint_address;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_delivery_logagent_time;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_delivery_customer_time;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_delivery_customer_city;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_delivery_logagent_gln;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_deliverypoint_gln;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $customer_comment;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $client_id;
    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $processed_by_era_date;
    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $processed_by_eshop_date;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $delivery_cost_sum;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $packet_id;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $processed_by_era_error_message;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $processed_by_eshop_error_message;
    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $date_insert;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_delivery_customer_street;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_delivery_customer_building;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_delivery_customer_flat;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_delivery_customer_post_index;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $order_delivery_customer_house;
    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $customer_desired_date;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $customer_desired_time_from;
    /**
     * @var string
     * @ORM\Column(type="string",nullable=true)
     */
    protected $customer_desired_time_to;
    /**
     * @var integer
     * @ORM\Column(type="integer",nullable=true)
     */
    protected $es_delivery_scheme;

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
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
    public function getOrderStatus()
    {
        return $this->order_status;
    }

    /**
     * @param $order_status
     */
    public function setOrderStatus($order_status)
    {
        $this->order_status = $order_status;
    }

    /**
     * @return string
     */
    public function getOrderSum()
    {
        return $this->order_sum;
    }

    /**
     * @param $order_sum
     */
    public function setOrderSum($order_sum)
    {
        $this->order_sum = $order_sum;
    }

    /**
     * @return
     */
    public function getOrderDate()
    {
        return $this->order_date;
    }

    /**
     * @param $order_date
     */
    public function setOrderDate($order_date)
    {
        $this->order_date = $order_date;
    }

    /**
     * @return string
     */
    public function getOrderPaymentType()
    {
        return $this->order_payment_type;
    }

    /**
     * @param $order_payment_type
     */
    public function setOrderPaymentType($order_payment_type)
    {
        $this->order_payment_type = $order_payment_type;
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
    public function getProductPricelistParam()
    {
        return $this->product_pricelist_param;
    }

    /**
     * @param $product_pricelist_param
     */
    public function setProductPricelistParam($product_pricelist_param)
    {
        $this->product_pricelist_param = $product_pricelist_param;
    }

    /**
     * @return string
     */
    public function getOrderDeliveryType()
    {
        return $this->order_delivery_type;
    }

    /**
     * @param $order_delivery_type
     */
    public function setOrderDeliveryType($order_delivery_type)
    {
        $this->order_delivery_type = $order_delivery_type;
    }

    /**
     * @return string
     */
    public function getOrderCustomerName()
    {
        return $this->order_customer_name;
    }

    /**
     * @param $order_customer_name
     */
    public function setOrderCustomerName($order_customer_name)
    {
        $this->order_customer_name = $order_customer_name;
    }

    /**
     * @return string
     */
    public function getOrderCustomerPhone()
    {
        return $this->order_customer_phone;
    }

    /**
     * @param $order_customer_phone
     */
    public function setOrderCustomerPhone($order_customer_phone)
    {
        $this->order_customer_phone = $order_customer_phone;
    }

    /**
     * @return string
     */
    public function getOrderCustomerEmail()
    {
        return $this->order_customer_email;
    }

    /**
     * @param $order_customer_email
     */
    public function setOrderCustomerEmail($order_customer_email)
    {
        $this->order_customer_email = $order_customer_email;
    }

    /**
     * @return string
     */
    public function getOrderSourceIdentifier()
    {
        return $this->order_source_identifier;
    }

    /**
     * @param $order_source_identifier
     */
    public function setOrderSourceIdentifier($order_source_identifier)
    {
        $this->order_source_identifier = $order_source_identifier;
    }

    /**
     * @return string
     */
    public function getOrderLogagentName()
    {
        return $this->order_logagent_name;
    }

    /**
     * @param $order_logagent_name
     */
    public function setOrderLogagentName($order_logagent_name)
    {
        $this->order_logagent_name = $order_logagent_name;
    }

    /**
     * @return string
     */
    public function getOrderLogagentPhone()
    {
        return $this->order_logagent_phone;
    }

    /**
     * @param $order_logagent_phone
     */
    public function setOrderLogagentPhone($order_logagent_phone)
    {
        $this->order_logagent_phone = $order_logagent_phone;
    }

    /**
     * @return string
     */
    public function getOrderLogagentEmail()
    {
        return $this->order_logagent_email;
    }

    /**
     * @param $order_logagent_email
     */
    public function setOrderLogagentEmail($order_logagent_email)
    {
        $this->order_logagent_email = $order_logagent_email;
    }

    /**
     * @return string
     */
    public function getOrderDeliveryAddress()
    {
        return $this->order_delivery_address;
    }

    /**
     * @param $order_delivery_address
     */
    public function setOrderDeliveryAddress($order_delivery_address)
    {
        $this->order_delivery_address = $order_delivery_address;
    }

    /**
     * @return string
     */
    public function getUserDcardId()
    {
        return $this->user_dcard_id;
    }

    /**
     * @param $user_dcard_id
     */
    public function setUserDcardId($user_dcard_id)
    {
        $this->user_dcard_id = $user_dcard_id;
    }

    /**
     * @return string
     */
    public function getOrderDeliveryLogagentDate()
    {
        return $this->order_delivery_logagent_date;
    }

    /**
     * @param $order_delivery_logagent_date
     */
    public function setOrderDeliveryLogagentDate($order_delivery_logagent_date)
    {
        $this->order_delivery_logagent_date = $order_delivery_logagent_date;
    }

    /**
     * @return string
     */
    public function getOrderDeliveryCustomerDate()
    {
        return $this->order_delivery_customer_date;
    }

    /**
     * @param $order_delivery_customer_date
     */
    public function setOrderDeliveryCustomerDate($order_delivery_customer_date)
    {
        $this->order_delivery_customer_date = $order_delivery_customer_date;
    }

    /**
     * @return string
     */
    public function getOrderDeliverypointName()
    {
        return $this->order_deliverypoint_name;
    }

    /**
     * @param $order_deliverypoint_name
     */
    public function setOrderDeliverypointName($order_deliverypoint_name)
    {
        $this->order_deliverypoint_name = $order_deliverypoint_name;
    }

    /**
     * @return string
     */
    public function getOrderDeliverypointPhone()
    {
        return $this->order_deliverypoint_phone;
    }

    /**
     * @param $order_deliverypoint_phone
     */
    public function setOrderDeliverypointPhone($order_deliverypoint_phone)
    {
        $this->order_deliverypoint_phone = $order_deliverypoint_phone;
    }

    /**
     * @return string
     */
    public function getOrderDeliverypointEmail()
    {
        return $this->order_deliverypoint_email;
    }

    /**
     * @param $order_deliverypoint_email
     */
    public function setOrderDeliverypointEmail($order_deliverypoint_email)
    {
        $this->order_deliverypoint_email = $order_deliverypoint_email;
    }

    /**
     * @return string
     */
    public function getOrderDeliverypointAddress()
    {
        return $this->order_deliverypoint_address;
    }

    /**
     * @param $order_deliverypoint_address
     */
    public function setOrderDeliverypointAddress($order_deliverypoint_address)
    {
        $this->order_deliverypoint_address = $order_deliverypoint_address;
    }

    /**
     * @return string
     */
    public function getOrderDeliveryLogagentTime()
    {
        return $this->order_delivery_logagent_time;
    }

    /**
     * @param $order_delivery_logagent_time
     */
    public function setOrderDeliveryLogagentTime($order_delivery_logagent_time)
    {
        $this->order_delivery_logagent_time = $order_delivery_logagent_time;
    }

    /**
     * @return string
     */
    public function getOrderDeliveryCustomerTime()
    {
        return $this->order_delivery_customer_time;
    }

    /**
     * @param $order_delivery_customer_time
     */
    public function setOrderDeliveryCustomerTime($order_delivery_customer_time)
    {
        $this->order_delivery_customer_time = $order_delivery_customer_time;
    }

    /**
     * @return string
     */
    public function getOrderDeliveryCustomerCity()
    {
        return $this->order_delivery_customer_city;
    }

    /**
     * @param $order_delivery_customer_city
     */
    public function setOrderDeliveryCustomerCity($order_delivery_customer_city)
    {
        $this->order_delivery_customer_city = $order_delivery_customer_city;
    }

    /**
     * @return string
     */
    public function getOrderDeliveryLogagentGln()
    {
        return $this->order_delivery_logagent_gln;
    }

    /**
     * @param $order_delivery_logagent_gln
     */
    public function setOrderDeliveryLogagentGln($order_delivery_logagent_gln)
    {
        $this->order_delivery_logagent_gln = $order_delivery_logagent_gln;
    }

    /**
     * @return string
     */
    public function getOrderDeliverypointGln()
    {
        return $this->order_deliverypoint_gln;
    }

    /**
     * @param $order_deliverypoint_gln
     */
    public function setOrderDeliverypointGln($order_deliverypoint_gln)
    {
        $this->order_deliverypoint_gln = $order_deliverypoint_gln;
    }

    /**
     * @return string
     */
    public function getCustomerComment()
    {
        return $this->customer_comment;
    }

    /**
     * @param $customer_comment
     */
    public function setCustomerComment($customer_comment)
    {
        $this->customer_comment = $customer_comment;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @param $client_id
     */
    public function setClientId($client_id)
    {
        $this->client_id = $client_id;
    }

    /**
     * @return \DateTime
     */
    public function getProcessedByEraDate()
    {
        return $this->processed_by_era_date;
    }

    /**
     * @param $processed_by_era_date
     */
    public function setProcessedByEraDate($processed_by_era_date)
    {
        $this->processed_by_era_date = $processed_by_era_date;
    }

    /**
     * @return \DateTime
     */
    public function getProcessedByEshopDate()
    {
        return $this->processed_by_eshop_date;
    }

    /**
     * @param $processed_by_eshop_date
     */
    public function setProcessedByEshopDate($processed_by_eshop_date)
    {
        $this->processed_by_eshop_date = $processed_by_eshop_date;
    }

    /**
     * @return string
     */
    public function getDeliveryCostSum()
    {
        return $this->delivery_cost_sum;
    }

    /**
     * @param $delivery_cost_sum
     */
    public function setDeliveryCostSum($delivery_cost_sum)
    {
        $this->delivery_cost_sum = $delivery_cost_sum;
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
    public function getProcessedByEraErrorMessage()
    {
        return $this->processed_by_era_error_message;
    }

    /**
     * @param $processed_by_era_error_message
     */
    public function setProcessedByEraErrorMessage($processed_by_era_error_message)
    {
        $this->processed_by_era_error_message = $processed_by_era_error_message;
    }

    /**
     * @return string
     */
    public function getProcessedByEshopErrorMessage()
    {
        return $this->processed_by_eshop_error_message;
    }

    /**
     * @param $processed_by_eshop_error_message
     */
    public function setProcessedByEshopErrorMessage($processed_by_eshop_error_message)
    {
        $this->processed_by_eshop_error_message = $processed_by_eshop_error_message;
    }

    /**
     * @return
     */
    public function getDateInsert()
    {
        return $this->date_insert;
    }

    /**
     * @param $date_insert
     */
    public function setDateInsert($date_insert)
    {
        $this->date_insert = $date_insert;
    }

    /**
     * @return string
     */
    public function getOrderDeliveryCustomerStreet()
    {
        return $this->order_delivery_customer_street;
    }

    /**
     * @param $order_delivery_customer_street
     */
    public function setOrderDeliveryCustomerStreet($order_delivery_customer_street)
    {
        $this->order_delivery_customer_street = $order_delivery_customer_street;
    }

    /**
     * @return string
     */
    public function getOrderDeliveryCustomerBuilding()
    {
        return $this->order_delivery_customer_building;
    }

    /**
     * @param $order_delivery_customer_building
     */
    public function setOrderDeliveryCustomerBuilding($order_delivery_customer_building)
    {
        $this->order_delivery_customer_building = $order_delivery_customer_building;
    }

    /**
     * @return string
     */
    public function getOrderDeliveryCustomerFlat()
    {
        return $this->order_delivery_customer_flat;
    }

    /**
     * @param $order_delivery_customer_flat
     */
    public function setOrderDeliveryCustomerFlat($order_delivery_customer_flat)
    {
        $this->order_delivery_customer_flat = $order_delivery_customer_flat;
    }

    /**
     * @return string
     */
    public function getOrderDeliveryCustomerPostIndex()
    {
        return $this->order_delivery_customer_post_index;
    }

    /**
     * @param $order_delivery_customer_post_index
     */
    public function setOrderDeliveryCustomerPostIndex($order_delivery_customer_post_index)
    {
        $this->order_delivery_customer_post_index = $order_delivery_customer_post_index;
    }

    /**
     * @return string
     */
    public function getOrderDeliveryCustomerHouse()
    {
        return $this->order_delivery_customer_house;
    }

    /**
     * @param $order_delivery_customer_house
     */
    public function setOrderDeliveryCustomerHouse($order_delivery_customer_house)
    {
        $this->order_delivery_customer_house = $order_delivery_customer_house;
    }

    /**
     * @return
     */
    public function getCustomerDesiredDate()
    {
        return $this->customer_desired_date;
    }

    /**
     * @param $customer_desired_date
     */
    public function setCustomerDesiredDate($customer_desired_date)
    {
        $this->customer_desired_date = $customer_desired_date;
    }

    /**
     * @return string
     */
    public function getCustomerDesiredTimeFrom()
    {
        return $this->customer_desired_time_from;
    }

    /**
     * @param $customer_desired_time_from
     */
    public function setCustomerDesiredTimeFrom($customer_desired_time_from)
    {
        $this->customer_desired_time_from = $customer_desired_time_from;
    }

    /**
     * @return string
     */
    public function getCustomerDesiredTimeTo()
    {
        return $this->customer_desired_time_to;
    }

    /**
     * @param $customer_desired_time_to
     */
    public function setCustomerDesiredTimeTo($customer_desired_time_to)
    {
        $this->customer_desired_time_to = $customer_desired_time_to;
    }

    /**
     * @return integer
     */
    public function getEsDeliveryScheme()
    {
        return $this->es_delivery_scheme;
    }

    /**
     * @param $es_delivery_scheme
     */
    public function setEsDeliveryScheme($es_delivery_scheme)
    {
        $this->es_delivery_scheme = $es_delivery_scheme;
    }

}