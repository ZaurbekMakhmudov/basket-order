<?php

namespace App\BasketOrderBundle\Entity;

use App\BasketOrderBundle\Repository\OrderRequestRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="order_request",
 *     options={"comment" = "Запросы заказов" },
 *     indexes={@ORM\Index(name="IDX_ORDER_REQUEST_ORDER_ID", columns={"order_id"})}
 * )
 * @ORM\Entity(repositoryClass=OrderRequestRepository::class)
 */
class OrderRequest
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=50, name="order_id", options={"comment" = "Идентификатор заказа"})
     */
    private string $orderId;

    /**
     * @ORM\Column(
     *     type="string",
     *     length=255,
     *     name="url_path",
     *     nullable=false,
     *     options={"comment" = "Путь адреса запроса"}
     * )
     */
    private string $urlPath;

    /**
     * @ORM\Column(
     *     type="string",
     *     length=255,
     *     name="utm_source",
     *     nullable=true,
     *     options={"comment" = "Источник трафика"}
     * )
     */
    private ?string $utmSource = null;

    /**
     * @ORM\Column(
     *     type="string",
     *     length=255,
     *     name="utm_campaign",
     *     nullable=true,
     *     options={"comment" = "Рекламная кампания"}
     * )
     */
    private ?string $utmCampaign = null;

    /**
     * @ORM\Column(
     *     name="created_at",
     *     type="datetime_immutable",
     *     options={"comment" = "Время создания"}
     * )
     */
    private \DateTimeImmutable $createdAt;

    public function __construct(string $orderId, string $urlPath)
    {
        $this->orderId = $orderId;
        $this->urlPath = $urlPath;
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     */
    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * @return string
     */
    public function getUrlPath(): string
    {
        return $this->urlPath;
    }

    /**
     * @param string $urlPath
     */
    public function setUrlPath(string $urlPath): void
    {
        $this->urlPath = $urlPath;
    }

    /**
     * @return string|null
     */
    public function getUtmSource(): ?string
    {
        return $this->utmSource;
    }

    /**
     * @param string|null $utmSource
     */
    public function setUtmSource(?string $utmSource): void
    {
        $this->utmSource = $utmSource;
    }

    /**
     * @return string|null
     */
    public function getUtmCampaign(): ?string
    {
        return $this->utmCampaign;
    }

    /**
     * @param string|null $utmCampaign
     */
    public function setUtmCampaign(?string $utmCampaign): void
    {
        $this->utmCampaign = $utmCampaign;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeImmutable $createdAt
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
