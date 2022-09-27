<?php

namespace App\BasketOrderBundle\Entity;

use App\BasketOrderBundle\Repository\CouponRestrictionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="coupon_restriction", options={"comment" = "ограничения купонов"}, indexes={@ORM\Index(name="IDX_COUPON_RESTRICTION_ID_RESTRICTION_COUPONS", columns={"idientifikator_restrictions_coupons"})})
 * @ORM\Entity(repositoryClass=CouponRestrictionRepository::class)
 */
class CouponRestriction
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="creator", type="string", length=255, nullable=true, options={"comment" = "Создатель"})
     */
    private $creator;

    /**
     * @ORM\Column(name="zaniato_do", type="datetime", nullable=true, options={"comment" = "Занято до"})
     */
    private $zaniatoDo;

    /**
     * @ORM\Column(name="idientifikator_restrictions_coupons", type="integer", nullable=true, options={"comment" = "Идентификатор"})
     */
    private $idientifikatorRestrictionsCoupons;

    /**
     * @ORM\Column(name="name_adv_promo_condition_sap", type="string", length=255, nullable=true, options={"comment" = "Название (SAP)"})
     */
    private $nameAdvPromoConditionSap;

    /**
     * @ORM\Column(name="name_adv_promo_condition_full_sap", type="string", length=255, nullable=true, options={"comment" = "Название полное (SAP)"})
     */
    private $nameAdvPromoConditionFullSap;

    /**
     * @ORM\Column(name="inserted_at", type="datetime", nullable=true, options={"comment" = "Дата-время вставки записи"})
     */
    private $insertedAt;

    /**
     * @ORM\ManyToMany(targetEntity=Coupon::class, mappedBy="couponRestriction")
     */
    private $coupons;

    public function __construct()
    {
        $this->coupons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getZaniatoDo(): ?\DateTimeInterface
    {
        return $this->zaniatoDo;
    }

    public function setZaniatoDo(?\DateTimeInterface $zaniatoDo): self
    {
        $this->zaniatoDo = $zaniatoDo;

        return $this;
    }

    public function getIdientifikatorRestrictionsCoupons(): ?int
    {
        return $this->idientifikatorRestrictionsCoupons;
    }

    public function setIdientifikatorRestrictionsCoupons(?int $idientifikatorRestrictionsCoupons): self
    {
        $this->idientifikatorRestrictionsCoupons = $idientifikatorRestrictionsCoupons;

        return $this;
    }

    public function getNameAdvPromoConditionSap(): ?string
    {
        return $this->nameAdvPromoConditionSap;
    }

    public function setNameAdvPromoConditionSap(?string $nameAdvPromoConditionSap): self
    {
        $this->nameAdvPromoConditionSap = $nameAdvPromoConditionSap;

        return $this;
    }

    public function getNameAdvPromoConditionFullSap(): ?string
    {
        return $this->nameAdvPromoConditionFullSap;
    }

    public function setNameAdvPromoConditionFullSap(?string $nameAdvPromoConditionFullSap): self
    {
        $this->nameAdvPromoConditionFullSap = $nameAdvPromoConditionFullSap;

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
     * @return Collection|Coupon[]
     */
    public function getCoupons(): Collection
    {
        return $this->coupons;
    }

    public function addCoupon(Coupon $coupon): self
    {
        if (!$this->coupons->contains($coupon)) {
            $this->coupons[] = $coupon;
            $coupon->addCouponRestriction($this);
        }

        return $this;
    }

    public function removeCoupon(Coupon $coupon): self
    {
        if ($this->coupons->removeElement($coupon)) {
            $coupon->removeCouponRestriction($this);
        }

        return $this;
    }

}
