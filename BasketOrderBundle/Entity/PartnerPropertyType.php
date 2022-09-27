<?php

namespace App\BasketOrderBundle\Entity;

use App\Repository\BasketOrderBundle\Entity\PartnerPropertyTypeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="partner_property_type", options={"comment" = "Типы данных от партнера" })
 * @ORM\Entity(repositoryClass=PartnerPropertyTypeRepository::class)
 */
class PartnerPropertyType
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
}
