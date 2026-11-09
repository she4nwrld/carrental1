<?php

namespace App\Entity;

use App\Repository\PromoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PromoRepository::class)]
class Promo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 40)]
    private string $tag = '';

    #[ORM\Column(length: 120)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $blurb = '';

    #[ORM\Column]
    private int $percent = 0;

    #[ORM\Column(name: 'free_days')]
    private int $freeDays = 0;

    #[ORM\Column(name: 'min_days')]
    private int $minDays = 0;

    #[ORM\Column(name: 'min_advance_days')]
    private int $minAdvanceDays = 0;

    #[ORM\Column]
    private int $stackable = 0;

    #[ORM\Column(name: 'expires_at', length: 20, nullable: true)]
    private ?string $expiresAt = null;

    #[ORM\Column(name: 'sort_order')]
    private int $sortOrder = 0;

    #[ORM\Column]
    private int $active = 1;

    private int $uses = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code !== null && trim($code) !== '' ? strtoupper(trim($code)) : null;

        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setTag(string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getBlurb(): string
    {
        return $this->blurb;
    }

    public function setBlurb(string $blurb): static
    {
        $this->blurb = $blurb;

        return $this;
    }

    public function getPercent(): int
    {
        return $this->percent;
    }

    public function setPercent(int $percent): static
    {
        $this->percent = $percent;

        return $this;
    }

    public function getFreeDays(): int
    {
        return $this->freeDays;
    }

    public function getFree_days(): int
    {
        return $this->freeDays;
    }

    public function setFreeDays(int $freeDays): static
    {
        $this->freeDays = $freeDays;

        return $this;
    }

    public function getMinDays(): int
    {
        return $this->minDays;
    }

    public function getMin_days(): int
    {
        return $this->minDays;
    }

    public function setMinDays(int $minDays): static
    {
        $this->minDays = $minDays;

        return $this;
    }

    public function getMinAdvanceDays(): int
    {
        return $this->minAdvanceDays;
    }

    public function getMin_advance_days(): int
    {
        return $this->minAdvanceDays;
    }

    public function setMinAdvanceDays(int $minAdvanceDays): static
    {
        $this->minAdvanceDays = $minAdvanceDays;

        return $this;
    }

    public function getStackable(): int
    {
        return $this->stackable;
    }

    public function setStackable(int $stackable): static
    {
        $this->stackable = $stackable;

        return $this;
    }

    public function getExpiresAt(): ?string
    {
        return $this->expiresAt;
    }

    public function getExpires_at(): ?string
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?string $expiresAt): static
    {
        $this->expiresAt = ($expiresAt !== null && trim($expiresAt) !== '') ? trim($expiresAt) : null;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function getSort_order(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getActive(): int
    {
        return $this->active;
    }

    public function setActive(int $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getUses(): int
    {
        return $this->uses;
    }

    public function setUses(int $uses): static
    {
        $this->uses = $uses;

        return $this;
    }
}
