<?php

namespace App\BasketOrderBundle\Notification;

/**
 * Class CouponNotification
 * @package App\BasketOrderBundle\Notification
 */
class CouponNotification extends Notification
{
    private int $code;

    private string $level;

    private string $message;

    private string $reason;

    private string $coupon;

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function setLevel(string $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getCoupon(): string
    {
        return $this->coupon;
    }

    public function setCoupon(string $couponNumber): self
    {
        $this->coupon = $couponNumber;

        return $this;
    }

}