<?php

namespace App\BasketOrderBundle\Entity;

use App\Repository\BasketOrderBundle\Entity\PartnerOrderDataRepository;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Integer;

/**
 * @ORM\Table(name="partner_order_data", options={"comment" = "Значения данных партнера" })
 * @ORM\Entity(repositoryClass="App\BasketOrderBundle\Repository\PartnerOrderDataRepository")
 */
class PartnerOrderData
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="property_title")
     */
    private $propertyTitle;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $value;

    /**
     * @ORM\Column(type="string", length=255, name="order_id")\
     */
    private $orderId;

    /**
     * @ORM\Column(type="string", name="partner_order_id")
     */
    private $partnerOrderId;

    /**
     * @ORM\Column(type="integer", name="partner_sap_id")
     */
    private $partnerSapId;


    public function getPartnerOrderId(): ?string
    {
        return $this->partnerOrderId;
    }

    public function setPartnerOrderId(string $partnerOrderId): self
    {
        $this->partnerOrderId = $partnerOrderId;

        return $this;
    }

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

    public function getPartnerSapId(): ?int
    {
        return $this->partnerSapId;
    }

    public function setPartnerSapId($partnerSapId)
    {
        $this->partnerSapId = $partnerSapId;

        return $this;
    }
}
