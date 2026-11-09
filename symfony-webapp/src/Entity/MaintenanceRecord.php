<?php

namespace App\Entity;

use App\Repository\MaintenanceRecordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaintenanceRecordRepository::class)]
class MaintenanceRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'car_id')]
    private int $carId = 0;

    #[ORM\Column(name: 'car_name', length: 100)]
    private string $carName = '';

    #[ORM\Column(name: 'car_type', length: 40)]
    private string $carType = '';

    #[ORM\Column(name: 'car_img', length: 150)]
    private string $carImg = 'images/toyota-vios.png';

    #[ORM\Column(name: 'start_date', length: 20)]
    private string $startDate = '';

    #[ORM\Column(name: 'end_date', length: 20)]
    private string $endDate = '';

    #[ORM\Column]
    private int $cost = 0;

    #[ORM\Column(length: 255)]
    private string $description = '';

    #[ORM\Column]
    private bool $resolved = false;

    #[ORM\Column(name: 'created_at', length: 30)]
    private string $createdAt = '';

    private int $clashes = 0;

    public function __construct()
    {
        $this->createdAt = date('Y-m-d H:i:s');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCarId(): int
    {
        return $this->carId;
    }

    public function getCar_id(): int
    {
        return $this->carId;
    }

    public function setCarId(int $carId): static
    {
        $this->carId = $carId;

        return $this;
    }

    public function getCarName(): string
    {
        return $this->carName;
    }

    public function getCar_name(): string
    {
        return $this->carName;
    }

    public function setCarName(string $carName): static
    {
        $this->carName = $carName;

        return $this;
    }

    public function getCarType(): string
    {
        return $this->carType;
    }

    public function getCar_type(): string
    {
        return $this->carType;
    }

    public function setCarType(string $carType): static
    {
        $this->carType = $carType;

        return $this;
    }

    public function getCarImg(): string
    {
        return $this->carImg;
    }

    public function getCar_img(): string
    {
        return $this->carImg;
    }

    public function setCarImg(string $carImg): static
    {
        $this->carImg = $carImg;

        return $this;
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getStart_date(): string
    {
        return $this->startDate;
    }

    public function setStartDate(string $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): string
    {
        return $this->endDate;
    }

    public function getEnd_date(): string
    {
        return $this->endDate;
    }

    public function setEndDate(string $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getCost(): int
    {
        return $this->cost;
    }

    public function setCost(int $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function getResolved(): bool
    {
        return $this->resolved;
    }

    public function setResolved(bool $resolved): static
    {
        $this->resolved = $resolved;

        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getCreated_at(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getClashes(): int
    {
        return $this->clashes;
    }

    public function setClashes(int $clashes): static
    {
        $this->clashes = $clashes;

        return $this;
    }
}
