<?php

namespace App\BasketOrderBundle\Traits;

use App\BasketOrderBundle\Entity\Order;
use App\BasketOrderBundle\Helper\DateTimeHelper;
use App\BasketOrderBundle\Helper\ShopConst;
use DateTime;
use Exception;

/**
 * Trait _PaymentInformationTrait
 * @package App\BasketOrderBundle\Traits
 */
trait _PaymentInformationTrait
{
    /**
     * @param null $key
     * @return mixed
     */
    public function getPaymentInformationData($key = null)
    {
        $data = $this->paymentInformation;
        if ($data and isset($data[$key])) {
            return $data[$key];
        }
        return $data;
    }

    /**
     * @param null $key
     * @return null
     */
    public function findPaymentInformationData($key = null)
    {
        $data = $this->paymentInformation;
        if ($data and isset($data[$key])) {
            return $data[$key];
        }
        return null;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setPaymentInformationData($data)
    {
        $this->paymentInformation = $data;
        return $this;
    }

    /**
     * @param Order $order
     * @param string $status
     * @return $this
     * @throws Exception
     */
    public function setPaymentInformationDataFromStatus(Order $order, string $status)
    {
        $timeZone = new \DateTimeZone(DateTimeHelper::getInstance()->getTimeZone());
        $date = new \DateTime('now', $timeZone);
        $curdate = $date->format(ShopConst::ORDER_FORMAT_DATE_TIME);
        $paymentInformation = [
            'status' => ShopConst::getPaymentStatusRM($status),
            'date'   => $curdate,
            'amount' => $order->getCost()
        ];
        $this->setPaymentInformationData($paymentInformation);

        return $this;
    }

    /**
     * @return null
     */
    public function getPaymentInformationStatus()
    {
        return $this->findPaymentInformationData('status');
    }

    /**
     * @return null
     */
    public function getPaymentInformationAmount()
    {
        return $this->findPaymentInformationData('amount');
    }

    /**
     * @return DateTime|false|null
     */
    public function getPaymentInformationDate()
    {
        $date = $this->findPaymentInformationData('date');
        if ($date and preg_match(ShopConst::ORDER_PATTERN_DATE_TIME, $date)) {
            $timeZone = new \DateTimeZone(DateTimeHelper::getInstance()->getTimeZone());
            return DateTime::createFromFormat(ShopConst::ORDER_FORMAT_DATE_TIME, $date)->setTimezone($timeZone);
        }
        return null;
    }
}