<?php

namespace App\BasketOrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="delay", options={"comment" = "задержки" }, indexes={@ORM\Index(name="IDX_DELAY_BASKET_ID", columns={"basket_id"})})
 * @ORM\Entity(repositoryClass="App\BasketOrderBundle\Repository\DelayRepository")
 */
class Delay
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=true, options={"comment" = "дата и время запроса" })
     */
    private $executed;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"comment" = "ИД корзины" })
     */
    private $basket_id;

    /**
     * @ORM\Column(type="string", length=25, nullable=true, options={"comment" = "тип запроса" })
     */
    private $request;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true, options={"comment" = "задержка" })
     */
    private $delay;

    /**
     * @ORM\Column(name="executed_exactly", type="decimal", precision=13, scale=3, nullable=true, options={"comment" = "точное дата и время запроса" })
     */
    private $executedExactly;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExecuted(): ?\DateTimeInterface
    {
        return $this->executed;
    }

    public function setExecuted(?\DateTimeInterface $executed): self
    {
        $this->executed = $executed;

        return $this;
    }

    public function getBasketId(): ?int
    {
        return $this->basket_id;
    }

    public function setBasketId(?int $basket_id): self
    {
        $this->basket_id = $basket_id;

        return $this;
    }

    public function getRequest(): ?string
    {
        return $this->request;
    }

    public function setRequest(?string $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function getDelay()
    {
        return $this->delay;
    }

    public function setDelay($delay): self
    {
        $this->delay = $delay;

        return $this;
    }

    public function getExecutedExactly(): ?string
    {
        return $this->executedExactly;
    }

    public function setExecutedExactly(?string $executedExactly): self
    {
        $this->executedExactly = $executedExactly;

        return $this;
    }
}
