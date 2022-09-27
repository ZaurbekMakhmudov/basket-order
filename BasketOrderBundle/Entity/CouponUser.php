<?php

namespace App\BasketOrderBundle\Entity;

use App\BasketOrderBundle\Repository\CouponUserRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="coupon_user", options={"comment" = "применение купонов пользователями"}, indexes={@ORM\Index(name="IDX_COUPON_USER_USER_ID_COUPON_NUMBER", columns={"user_id","coupon_number"})})
 * @ORM\Entity(repositoryClass=CouponUserRepository::class)
 */
class CouponUser
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="user_id", type="string", length=255, nullable=true, options={"comment" = "ID пользователя"})
     */
    private $userId;

    /**
     * @ORM\Column(name="coupon_number", type="string", length=255, nullable=true, options={"comment" = "номер купона"})
     */
    private $couponNumber;

    /**
     * @ORM\Column(name="inserted_at", type="datetime", nullable=true, options={"comment" = "Дата-время вставки записи"})
     */
    private $insertedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCouponNumber(): ?string
    {
        return $this->couponNumber;
    }

    public function setCouponNumber(?string $couponNumber): self
    {
        $this->couponNumber = $couponNumber;

        return $this;
    }

    public function getInsertedAt(): ?\DateTimeInterface
    {
        return $this->insertedAt;
    }

    public function setInsertedAt(?\DateTimeInterface $insertedAt): self
    {
        $this->insertedAt = $insertedAt;

        return $this;
    }
}
