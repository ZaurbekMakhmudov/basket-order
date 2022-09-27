<?php

namespace App\BasketOrderBundle\Entity;

use App\Repository\BasketOrderBundle\Entity\PartnerRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="partner", options={"comment" = "Партнеры" })
 * @ORM\Entity(repositoryClass=PartnerRepository::class)
 */
class Partner
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="integer", name="partner_sap_id")
     */
    private $partnerSapId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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
