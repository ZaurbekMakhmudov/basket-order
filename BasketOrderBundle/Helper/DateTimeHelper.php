<?php

namespace App\BasketOrderBundle\Helper;

use DateTime;

class DateTimeHelper
{
    static protected $instance = null;
    /** @var  string */
    protected static $timeZone;

    /**
     * @param string $timeZone
     * @return null|$this
     */
    public static function getInstance($timeZone = 'Europe/Moscow')
    {
        if (static::$instance === null) {
            static::$instance = new self();
            date_default_timezone_set($timeZone);
            static::$timeZone = $timeZone;
        }

        return static::$instance;
    }

    public function setTimeZone($timeZone = 'Europe/Moscow')
    {

        static::$timeZone = $timeZone;
    }

    public function getTimeZone()
    {
        return static::$timeZone;
    }

    /**
     * @param DateTime|null $date
     * @return DateTime|null
     */
    public function getDateOnly(DateTime $date = null)
    {
        if ($date) {
            $timeZone = new \DateTimeZone(static::$timeZone);
            $date->setTimezone($timeZone);
            $out = $date->setTime(0, 0, 0);

            return $out;
        }

        return null;
    }

    /**
     * @param $str
     * @param null $format
     * @return bool|DateTime
     */
    public function getDateFromString($str = '', $format = null)
    {
        $format = $format ? $format : 'Y-m-d H:i:s';
        if (!empty($str)) {
            $timeZone = new \DateTimeZone(static::$timeZone);

            return \DateTime::createFromFormat($format, $str)->setTimezone($timeZone);
        }

        return static::getDateCurrent();
    }

    /**
     * @return DateTime
     */
    public function getDateCurrent(DateTime $date=null)
    {
        if($date==null){
            $date = new DateTime();
            $timeZone = new \DateTimeZone(static::$timeZone);
            $date->setTimezone($timeZone);
        }else{
            $timeZone = new \DateTimeZone(static::$timeZone);
            $date->setTimezone($timeZone);
        }

        return $date;
    }

    /**
     * @param null $date
     * @param null $format
     * @return string
     */
    public function getDateString(DateTime $date = null, $format = null, $nocurrent = false)
    {
        if($nocurrent && is_null($date)) {return null;}
        $format = $format ? $format : 'Y-m-d H:i:s';
        $date = static::getDateCurrent($date);
        return $date->format($format);
    }

    /**
     * @param null|DateTime $date
     * @param int $digits
     * @return string
     */
    public function getDateYear($date = null, $digits = 4)
    {
        $date = $date ? $date : static::getDateCurrent();
        if ($digits == 4) {
            $year = $date->format('Y');
        } else {
            $year = $date->format('y');
        }

        return $year;
    }
}