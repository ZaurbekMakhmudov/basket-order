<?php

namespace App\BasketOrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="history_order", options={"comment" = "история заказа" }, indexes={@ORM\Index(name="IDX_HISTORY_ORDER_ORDER_ID", columns={"order_id"})})
 * @ORM\Entity(repositoryClass="App\BasketOrderBundle\Repository\OrderHistoryRepository")
 */
class OrderHistory
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $order_id;

    /**
     * @ORM\Column(type="string", length=5)
     */
    private $status;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, nullable=true)
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $user;

    /**
     * @ORM\Column(type="datetime")
     */
    private $inserted;

    /**
     * дата последнего калькулейта
     * @ORM\Column(name="calculate", type="datetime", nullable=true, options={"comment" = "дата последнего калькулейта" })
     */
    protected $calculate;

    /**
     * дата последнего генерэйта
     * @ORM\Column(name="generate", type="datetime", nullable=true, options={"comment" = "дата последнего генерэйта" })
     */
    protected $generate;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, nullable=true)
     */
    private $cost;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, nullable=true)
     */
    private $cost_delivery;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderId(): ?string
    {
        return $this->order_id;
    }

    public function setOrderId(string $order_id): self
    {
        $this->order_id = $order_id;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getInserted(): ?\DateTimeInterface
    {
        return $this->inserted;
    }

    public function setInserted(\DateTimeInterface $inserted): self
    {
        $this->inserted = $inserted;

        return $this;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(?string $cost): self
    {
        $this->cost = $cost;

        return $this;
    }

    public function getCostDelivery(): ?string
    {
        return $this->cost_delivery;
    }

    public function setCostDelivery(?string $cost_delivery): self
    {
        $this->cost_delivery = $cost_delivery;

        return $this;
    }

    public function getCalculate()
    {
        return $this->calculate;
    }

    public function setCalculate(\DateTime $calculate): void
    {
        $this->calculate = $calculate;
    }

    public function getGenerate()
    {
        return $this->generate;
    }

    public function setGenerate(\DateTime $generate): void
    {
        $this->generate = $generate;
    }

}
