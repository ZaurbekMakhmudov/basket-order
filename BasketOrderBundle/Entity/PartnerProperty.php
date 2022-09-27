<?php

namespace App\BasketOrderBundle\Entity;

use App\Repository\BasketOrderBundle\Entity\PartnerPropertyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="partner_property", options={"comment" = "Свойства партнеров" })
 * @ORM\Entity(repositoryClass="App\BasketOrderBundle\Repository\PartnerPropertyRepository")
 */
class PartnerProperty
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
    private $type;


    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="integer", name="partner_id")
     */
    private $partnerId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    public function setPartnerId(int $partnerId): self
    {
        $this->partnerId = $partnerId;

        return $this;
    }
}
