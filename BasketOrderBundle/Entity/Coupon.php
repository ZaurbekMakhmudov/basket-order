<?php

namespace App\BasketOrderBundle\Entity;

use App\BasketOrderBundle\Repository\CouponRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\BasketOrderBundle\Entity\CouponRestriction;

/**
 * @ORM\Table(name="coupon", options={"comment" = "купоны"}, indexes={@ORM\Index(name="IDX_COUPON_COUPON_ID_CODE_DETAIL_SAP", columns={"coupon_id","code_detail_sap"})})
 * @ORM\Entity(repositoryClass=CouponRepository::class)
 */
class Coupon
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="status_autoname", type="string", length=255, nullable=true, options={"comment" = "Статус заполнения АН"})
     */
    private $statusAutoname;

    /**
     * @ORM\Column(name="creator", type="string", length=255, nullable=true, options={"comment" = "Создатель"})
     */
    private $creator;

    /**
     * @ORM\Column(name="price_sap", type="decimal", precision=15, scale=2, nullable=true, options={"comment" = "Цена тарифа (SAP)"})
     */
    private $priceSap;

    /**
     * @ORM\Column(name="discount_value_sap", type="decimal", precision=15, scale=2, nullable=true, options={"comment" = "Скидка (SAP)"})
     */
    private $discountValueSap;

    /**
     * @ORM\Column(name="code_record_sap", type="integer", nullable=true, options={"comment" = "Код (SAP)"})
     */
    private $codeRecordSap;

    /**
     * @ORM\Column(name="name_type_record_sap", type="string", length=255, nullable=true, options={"comment" = "Механика (SAP)"})
     */
    private $nameTypeRecordSap;

    /**
     * @ORM\Column(name="type_record_comment_sap", type="string", length=255, nullable=true, options={"comment" = "Комментарий к механике (SAP)"})
     */
    private $typeRecordCommentSap;

    /**
     * @ORM\Column(name="zaniato_do", type="datetime", nullable=true, options={"comment" = "Занято до"})
     */
    private $zaniatoDo;

    /**
     * @ORM\Column(name="coupon_id", type="integer", nullable=true, options={"comment" = "Идентификатор"})
     */
    private $couponId;

    /**
     * @ORM\Column(name="name_record_sap", type="string", length=255, nullable=true, options={"comment" = "Название (SAP)"})
     */
    private $nameRecordSap;

    /**
     * @ORM\Column(name="dat_begin_sap", type="datetime", nullable=true, options={"comment" = "Дата начала (SAP)"})
     */
    private $datBeginSap;

    /**
     * @ORM\Column(name="dat_end_sap", type="datetime", nullable=true, options={"comment" = "Дата окончания (SAP)"})
     */
    private $datEndSap;

    /**
     * @ORM\Column(name="code_detail_sap", type="string", length=255, nullable=true, options={"comment" = "ШК купона/промокод (SAP)"})
     */
    private $codeDetailSap;

    /**
     * @ORM\Column(name="an_verified_datetime", type="datetime", nullable=true, options={"comment" = "Дата-время установки статуса проверено"})
     */
    private $anVerifiedDateTime;

    /**
     * @ORM\Column(name="an_verified_date", type="date", nullable=true, options={"comment" = "Дата установки статуса проверено"})
     */
    private $anVerifiedDate;

    /**
     * @ORM\Column(name="inserted_at", type="datetime", nullable=true, options={"comment" = "Дата-время вставки записи"})
     */
    private $insertedAt;

    /**
     * @ORM\ManyToMany(targetEntity=CouponRestriction::class, inversedBy="coupons")
     */
    private $couponRestriction;

    public function __construct()
    {
        $this->couponRestriction = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatusAutoname(): ?string
    {
        return $this->statusAutoname;
    }

    public function setStatusAutoname(?string $statusAutoname): self
    {
        $this->statusAutoname = $statusAutoname;

        return $this;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }

    public function setCreator(?string $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getPriceSap(): ?string
    {
        return $this->priceSap;
    }

    public function setPriceSap(?string $priceSap): self
    {
        $this->priceSap = $priceSap;

        return $this;
    }

    public function getDiscountValueSap(): ?string
    {
        return $this->discountValueSap;
    }

    public function setDiscountValueSap(?string $discountValueSap): self
    {
        $this->discountValueSap = $discountValueSap;

        return $this;
    }

    public function getCodeRecordSap(): ?int
    {
        return $this->codeRecordSap;
    }

    public function setCodeRecordSap(?int $codeRecordSap): self
    {
        $this->codeRecordSap = $codeRecordSap;

        return $this;
    }

    public function getNameTypeRecordSap(): ?string
    {
        return $this->nameTypeRecordSap;
    }

    public function setNameTypeRecordSap(?string $nameTypeRecordSap): self
    {
        $this->nameTypeRecordSap = $nameTypeRecordSap;

        return $this;
    }

    public function getTypeRecordCommentSap(): ?string
    {
        return $this->typeRecordCommentSap;
    }

    public function setTypeRecordCommentSap(?string $typeRecordCommentSap): self
    {
        $this->typeRecordCommentSap = $typeRecordCommentSap;

        return $this;
    }

    public function getZaniatoDo(): ?\DateTimeInterface
    {
        return $this->zaniatoDo;
    }

    public function setZaniatoDo(?\DateTimeInterface $zaniatoDo): self
    {
        $this->zaniatoDo = $zaniatoDo;

        return $this;
    }

    public function getCouponId(): ?int
    {
        return $this->couponId;
    }

    public function setCouponId(?int $couponId): self
    {
        $this->couponId = $couponId;

        return $this;
    }

    public function getNameRecordSap(): ?string
    {
        return $this->nameRecordSap;
    }

    public function setNameRecordSap(?string $nameRecordSap): self
    {
        $this->nameRecordSap = $nameRecordSap;

        return $this;
    }

    public function getDatBeginSap(): ?\DateTimeInterface
    {
        return $this->datBeginSap;
    }

    public function setDatBeginSap(?\DateTimeInterface $datBeginSap): self
    {
        $this->datBeginSap = $datBeginSap;

        return $this;
    }

    public function getDatEndSap(): ?\DateTimeInterface
    {
        return $this->datEndSap;
    }

    public function setDatEndSap(?\DateTimeInterface $datEndSap): self
    {
        $this->datEndSap = $datEndSap;

        return $this;
    }

    public function getCodeDetailSap(): ?string
    {
        return $this->codeDetailSap;
    }

    public function setCodeDetailSap(?string $codeDetailSap): self
    {
        $this->codeDetailSap = $codeDetailSap;

        return $this;
    }

    public function getAnVerifiedDateTime(): ?\DateTimeInterface
    {
        return $this->anVerifiedDateTime;
    }

    public function setAnVerifiedDateTime(?\DateTimeInterface $anVerifiedDateTime): self
    {
        $this->anVerifiedDateTime = $anVerifiedDateTime;

        return $this;
    }

    public function getAnVerifiedDate(): ?\DateTimeInterface
    {
        return $this->anVerifiedDate;
    }

    public function setAnVerifiedDate(?\DateTimeInterface $anVerifiedDate): self
    {
        $this->anVerifiedDate = $anVerifiedDate;

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

    /**
     * @return Collection|CouponRestriction[]
     */
    public function getCouponRestriction(): Collection
    {
        return $this->couponRestriction;
    }

    public function addCouponRestriction(CouponRestriction $couponRestriction): self
    {
        if (!$this->couponRestriction->contains($couponRestriction)) {
            $this->couponRestriction[] = $couponRestriction;
        }

        return $this;
    }

    public function removeCouponRestriction(CouponRestriction $couponRestriction): self
    {
        $this->couponRestriction->removeElement($couponRestriction);

        return $this;
    }

}
