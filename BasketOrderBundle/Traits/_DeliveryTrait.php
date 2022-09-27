<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 25.07.19
 * Time: 12:04
 */

namespace App\BasketOrderBundle\Traits;

use App\BasketOrderBundle\Helper\DateTimeHelper;
use App\BasketOrderBundle\Helper\ShopConst;

trait _DeliveryTrait
{
    /**
     * @param null $key
     * @return mixed
     */
    public function getLogagentData($key = null)
    {
        $data = json_decode($this->logagentData, true);
        if ($data and isset($data[$key])) {

            return $data[$key];
        }

        return $data;
    }

    /**
     * @param null $key
     * @return null
     */
    public function findLogagentData($key = null)
    {
        $data = json_decode($this->logagentData, true);
        if ($data and isset($data[$key])) {

            return $data[$key];
        }

        return null;
    }

    /**
     * @param $data
     */
    public function setLogagentData($data)
    {
        if ($data) {

            $this->logagentData = json_encode($data, JSON_UNESCAPED_UNICODE);;
        }
    }

    /**
     * @param null $key
     * @return mixed
     */
    public function getDelivery($key = null)
    {
        $data = json_decode($this->delivery, true);
        if ($data and isset($data[$key])) {

            return $data[$key];
        }

        return $data;
    }

    /**
     * @param null $key
     * @return null
     */
    public function findDelivery($key = null)
    {
        $data = json_decode($this->delivery, true);
        if ($data and isset($data[$key])) {

            return $data[$key];
        }

        return null;
    }

    /**
     * @param $delivery
     */
    public function setDelivery($delivery)
    {
        if ($delivery) {
            $this->delivery = json_encode($delivery, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * @return null
     */
    public function getDeliveryName()
    {
        $data = $this->findDelivery('name');

        return $data;
    }

    /**
     * @return null
     */
    public function getDeliveryPhone()
    {
        $data = $this->findDelivery('phone');

        return $data;
    }

    /**
     * @return null
     */
    public function getDeliveryEmail()
    {
        $data = $this->findDelivery('email');

        return $data;
    }

    /**
     * @return null
     */
    public function getDeliveryAddress()
    {
        $data = $this->findDelivery('address');

        return $data;
    }

    /**
     * @return null
     */
    public function getDeliveryLogagentName()
    {
        $data = $this->findLogagentData('name');

        return $data;
    }

    /**
     * @return null
     */
    public function getDeliveryLogagentPhone()
    {
        $data = $this->findLogagentData('phone');

        return $data;
    }

    /**
     * @return null
     */
    public function getDeliveryLogagentEmail()
    {
        $data = $this->findLogagentData('email');

        return $data;
    }

    /**
     * @return bool|\DateTime|null
     */
    public function getDeliveryLogagentDate()
    {
        $data = $this->findLogagentData('date');
        if ($data and preg_match(ShopConst::ORDER_PATTERN_DATE, $data)) {
            $timeZone = new \DateTimeZone(DateTimeHelper::getInstance()->getTimeZone());

            return \DateTime::createFromFormat(ShopConst::ORDER_FORMAT_DATE, $data)->setTimezone($timeZone);
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function getDeliveryLogagentTime()
    {
        $data = $this->findLogagentData('time');

        return $data;
    }

    /**
     * @param $out
     * @return mixed
     */
    public function setDeliveryLogagentDate($out)
    {
        $dateStr = isset($out['date']) ? $out['date'] : null;
        if ($dateStr) {
            if (!preg_match(ShopConst::ORDER_PATTERN_DATE, $dateStr)) {
                $date = (DateTimeHelper::getInstance()->getDateCurrent())->modify('+3day');
                $dateStr = $date->format('Y-m-d');
            }
        } else {
            $date = (DateTimeHelper::getInstance()->getDateCurrent())->modify('+3day');
            $dateStr = $date->format('Y-m-d');
        }
        $out['date'] = $dateStr;

        return $out;
    }

    /**
     * @param $out
     * @return mixed
     */
    public function setOrderDeliveryLogagentAdd($out = null)
    {
        $deliveryType = $this->getDeliveryType();
        if ($deliveryType == ShopConst::DELIVERY_KEY_TYPE_E) {
            $out['logagent_gln'] = '1000043430';
            $out['point_gln'] = '1000043430/Курьер';
            $out['name'] = $this->getCustomerCity();
        }

        return $out;
    }
}