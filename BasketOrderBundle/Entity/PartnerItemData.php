<?php

namespace App\BasketOrderBundle\Entity;

use App\Repository\BasketOrderBundle\Entity\PartnerItemDataRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="partner_item_data", options={"comment" = "Данные о товарах от партнера" })
 * @ORM\Entity(repositoryClass="App\BasketOrderBundle\Repository\PartnerItemDataRepository")
 */
class PartnerItemData
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, name="property_title")
     */
    private $propertyTitle;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $value;

    /**
     * @ORM\Column(type="string", length=255, name="order_id")
     */
    private $orderId;

    /**
     * @ORM\Column(type="string", length=255, name="partner_order_id")
     */
    private $partnerOrderId;

    /**
     * @ORM\Column(type="integer", name="item_id")
     */
    private $itemId;

    /**
     * @ORM\Column(type="integer", name="partner_sap_id")
     */
    private $partnerSapId;

    /**
     * @ORM\Column(type="boolean", name="in_stock",  nullable=true, options={"comment" = "В наличии ли товар, 0-нет/не обработан, 1 -в наличии" })
     */
    private $inStock;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPropertyTitle(): ?string
    {
        return $this->propertyTitle;
    }

    public function setPropertyTitle(string $propertyTitle): self
    {
        $this->propertyTitle = $propertyTitle;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getPartnerOrderId(): ?string
    {
        return $this->partnerOrderId;
    }

    public function setPartnerOrderId(string $partnerOrderId): self
    {
        $this->partnerOrderId = $partnerOrderId;

        return $this;
    }

    public function getItemId(): ?int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): self
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function getPartnerSapId(): ?int
    {
        return $this->partnerSapId;
    }

    public function setPartnerSapId($partnerSapId)
    {
        $this->partnerSapId = $partnerSapId;

        return $this;
    }

    public function getInStock()
    {
        return $this->inStock;
    }

    public function setInStock($inStock)
    {
        $this->inStock = $inStock;

        return $this;
    }
}
