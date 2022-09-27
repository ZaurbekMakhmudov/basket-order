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

trait _CustomerTrait
{
    /**
     * @param null $key
     * @return mixed
     */
    public function getCustomerData($key = null)
    {
        $data = json_decode($this->customer, true);
        if ($data and isset($data[$key])) {

            return $data[$key];
        }

        return $data;
    }

    /**
     * @param null $key
     * @return null
     */
    public function findCustomerData($key = null)
    {
        $data = json_decode($this->customer, true);

        if ($data and isset($data[$key])) {

            return $data[$key];
        }

        return null;
    }

    /**
     * @param $data
     */
    public function setCustomerData($data)
    {
        $this->customer = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return null
     */
    public function getCustomerCity()
    {
        $data = $this->findCustomerData('city');

        return $data;
    }

    /**
     * @return null
     */
    public function getCustomerPostIndex()
    {
        $data = $this->findCustomerData('post_index');

        return $data;
    }

    /**
     * @return null
     */
    public function getCustomerStreet()
    {
        $data = $this->findCustomerData('street');

        return $data;
    }

    /**
     * @return null
     */
    public function getCustomerBuilding()
    {
        $data = $this->findCustomerData('building');

        return $data;
    }

    /**
     * @return null
     */
    public function getCustomerHouse()
    {
        $data = $this->findCustomerData('house');

        return $data;
    }

    /**
     * @return null
     */
    public function getCustomerFlat()
    {
        $data = $this->findCustomerData('flat');

        return $data;
    }

    /**
     * @return null
     */
    public function getCustomerName()
    {
        $data = $this->findCustomerData('name');

        return $data;
    }

    /**
     * @return null
     */
    public function getCustomerPhone()
    {
        $data = $this->findCustomerData('phone');

        return $data;
    }

    /**
     * @return null
     */
    public function getCustomerEmail()
    {
        $data = $this->findCustomerData('email');

        return $data;
    }

    /**
     * @return null
     */
    public function getCustomerTime()
    {
        $data = $this->findCustomerData('time');

        return $data;
    }

    /**
     * @return $this|null
     */
    public function getCustomerDate()
    {
        $data = $this->findCustomerData('date');
        if ($data and preg_match(ShopConst::ORDER_PATTERN_DATE, $data)) {
            $timeZone = new \DateTimeZone(DateTimeHelper::getInstance()->getTimeZone());
            return \DateTime::createFromFormat(ShopConst::ORDER_FORMAT_DATE, $data)->setTimezone($timeZone);
        }

        return null;
    }

    /**
     * @return $this|null
     */
    public function getCustomerDesiredDate()
    {
        $data = $this->findCustomerData('desired_date');
        if ($data and preg_match(ShopConst::ORDER_PATTERN_DATE, $data)) {
            $timeZone = new \DateTimeZone(DateTimeHelper::getInstance()->getTimeZone());
            return \DateTime::createFromFormat(ShopConst::ORDER_FORMAT_DATE, $data)->setTimezone($timeZone);
        }

        return null;
    }

    /**
     * @return null
     */
    public function getCustomerDesiredTimeFrom()
    {
        $data = $this->findCustomerData('desired_time_from');

        return $data;
    }

    /**
     * @return null
     */
    public function getCustomerDesiredTimeTo()
    {
        $data = $this->findCustomerData('desired_time_to');

        return $data;
    }

    /**
     * @return null
     */
    public function getCustomerComment()
    {
        $data = $this->findCustomerData('comment');

        return $data;
    }

    /**
     * @param $out
     * @return mixed
     */
    public function setCustomerPostIndex($out)
    {
        $city = isset($out['city']) ? $out['city'] : '';
        if ($city) {
            $citysM = ['москва', "мск"];
            $citysSP = ["санкт-петербург", "спб", "петербург", "с-петербург"];
            $city = mb_strtolower($city);
            if (in_array($city, $citysM)) {
                $postIndex = '101000';
            } elseif (in_array($city, $citysSP)) {
                $postIndex = '190000';
            } else {
                $postIndex = '190000';
            }
        } else {
            $postIndex = '';
        }

        $outPostIndex = isset($out['post_index']) ? $out['post_index'] : '';

        $deliveryType = $this->deliveryType;
        if (!$outPostIndex) {
            if ($deliveryType == ShopConst::DELIVERY_TYPE_E) {
                $postIndex ? $out['post_index'] = $postIndex : null;
            }
        }

        return $out;
    }
}