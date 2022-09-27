<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 25.07.19
 * Time: 14:46
 */

namespace App\BasketOrderBundle\Traits;

use App\BasketOrderBundle\Helper\DateTimeHelper;
use App\BasketOrderBundle\Helper\ShopConst;

trait _EshopOrderTrait
{
    public function getOrderStatus()
    {
        return $this->getStatus();
    }

    public function getOrderSum()
    {
        return $this->getCost();
    }

    /**
     * @return mixed|\DateTime
     */
    public function getOrderDate()
    {
        return $this->getCreated();
    }

    public function getOrderPaymentType()
    {
        return $this->getPaymentType();
    }

    public function getOrderDeliveryType()
    {
        return $this->getDeliveryType();
    }

    public function getOrderCustomerName()
    {
        return $this->getCustomerName();
    }

    public function getOrderCustomerPhone()
    {
        return $this->getCustomerPhone();
    }

    public function getOrderCustomerEmail()
    {
        return $this->getCustomerEmail();
    }

    public function getOrderSourceIdentifier()
    {
        return $this->getSourceIdentifier();
    }

    public function _getOrderLogagentName()
    {
    }

    public function getOrderLogagentPhone()
    {
        return $this->getDeliveryLogagentPhone();
    }

    public function getOrderLogagentEmail()
    {
        return $this->getDeliveryLogagentEmail();
    }

    public function getOrderDeliveryAddress()
    {
        return $this->getDeliveryAddress();
    }

    /**
     * @return mixed|\DateTime
     */
    public function getOrderDeliveryLogagentDate()
    {
        return $this->getDeliveryLogagentDate();
    }

    public function getOrderDeliveryCustomerDate()
    {
        return $this->getCustomerDate();
    }

    public function getOrderDeliverypointName()
    {
        return $this->getDeliveryName();
    }

    public function getOrderDeliverypointPhone()
    {
        return $this->getDeliveryPhone();
    }

    public function getOrderDeliverypointEmail()
    {
        return $this->getDeliveryEmail();
    }

    public function getOrderDeliverypointAddress()
    {
        return $this->getDeliveryAddress();
    }

    public function getOrderDeliveryLogagentTime()
    {
        return $this->getDeliveryLogagentTime();
    }

    public function getOrderDeliveryCustomerTime()
    {
        return $this->getCustomerTime();
    }

    public function getOrderDeliveryCustomerCity()
    {
        return $this->getCustomerCity();
    }

    public function getOrderDeliveryLogagentGln()
    {
        return $this->getLogagentGln();
    }

    public function getOrderDeliverypointGln()
    {
        return $this->getDeliveryPointGln();
    }

    /**
     * @return \DateTime
     */
    public function getProcessedByEshopDate()
    {
        return DateTimeHelper::getInstance()->getDateCurrent();
    }

    public function getOrderDeliveryCustomerStreet()
    {
        return $this->getCustomerStreet();
    }

    public function getOrderDeliveryCustomerBuilding()
    {
        return $this->getCustomerBuilding();
    }

    public function getOrderDeliveryCustomerFlat()
    {
        return $this->getCustomerFlat();
    }

    public function getOrderDeliveryCustomerPostIndex()
    {
        return $this->getCustomerPostIndex();
    }

    public function getOrderDeliveryCustomerHouse()
    {
        return $this->getCustomerHouse();
    }

///**************************************
    public function getUserDcardId()
    {

    }

    public function getClientId()
    {
        return null;
    }

    public function getPacketId()
    {
        return null;
    }

    public function getProcessedByEshopErrorMessage()
    {
        return '';
    }

    public function getDateInsert()
    {
        return null;
    }

    /**
     * @param $today
     */
    public function setEshopOrderData($today)
    {
        $status = $this->getStatus();
        $packetId = ShopConst::getGuid();
        $this->packetId = $packetId;
        if ($status == ShopConst::STATUS_CRE) {
            $this->eshopDate = $today;
        }

        $orderSourceIdentifier = $this->getSourceIdentifier(); //        "order_source_identifier": "6000000009",
        if (!$orderSourceIdentifier) {
            $this->setSourceIdentifier(ShopConst::UR_SAP_ID);
        }

        $productPricelistParam = $this->getProductPricelistParam(); //        "product_pricelist_param": "9900000009",
        if (!$productPricelistParam) {
            $this->setProductPricelistParam("9900000009");
        }
        $summa = $this->getDeliveryCostSum() + $this->getCost();
        $this->setOrderSum($summa);
    }

    public function setErrorMessage($errorMessage)
    {
        if (is_array($errorMessage)) {
            $errorMessage = json_encode($errorMessage);
        }
        $this->eshopErrorMessage = $errorMessage;
    }

    public function getErrorMessage()
    {

        return json_decode($this->eshopErrorMessage);
    }
}