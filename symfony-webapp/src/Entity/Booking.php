<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(name: 'car_id')]
    private int $carId = 0;

    #[ORM\Column(name: 'car_name', length: 100)]
    private string $carName = '';

    #[ORM\Column(name: 'car_img', length: 150)]
    private string $carImg = 'images/toyota-vios.png';

    #[ORM\Column(name: 'car_type', length: 40)]
    private string $carType = 'Sedan';

    #[ORM\Column(name: 'full_name', length: 100)]
    private string $fullName = '';

    #[ORM\Column(length: 120)]
    private string $email = '';

    #[ORM\Column(length: 40)]
    private string $phone = '';

    #[ORM\Column(name: 'pickup_date', length: 20)]
    private string $pickupDate = '';

    #[ORM\Column(name: 'return_date', length: 20)]
    private string $returnDate = '';

    #[ORM\Column(name: 'pickup_location', length: 80)]
    private string $pickupLocation = '';

    #[ORM\Column(name: 'return_location', length: 80)]
    private string $returnLocation = '';

    #[ORM\Column(name: 'driver_age', length: 30)]
    private string $driverAge = '26–65';

    #[ORM\Column]
    private int $days = 1;

    #[ORM\Column]
    private bool $delivery = false;

    #[ORM\Column(name: 'discount_code', length: 40, nullable: true)]
    private ?string $discountCode = null;

    #[ORM\Column]
    private int $subtotal = 0;

    #[ORM\Column]
    private int $discount = 0;

    #[ORM\Column]
    private int $total = 0;

    #[ORM\Column(length: 20)]
    private string $status = 'pending';

    #[ORM\Column(name: 'created_at', length: 30)]
    private string $createdAt = '';

    public function __construct()
    {
        $this->createdAt = date('Y-m-d H:i:s');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getUser_id(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;

        return $this;
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

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getFull_name(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPickupDate(): string
    {
        return $this->pickupDate;
    }

    public function getPickup_date(): string
    {
        return $this->pickupDate;
    }

    public function setPickupDate(string $pickupDate): static
    {
        $this->pickupDate = $pickupDate;

        return $this;
    }

    public function getReturnDate(): string
    {
        return $this->returnDate;
    }

    public function getReturn_date(): string
    {
        return $this->returnDate;
    }

    public function setReturnDate(string $returnDate): static
    {
        $this->returnDate = $returnDate;

        return $this;
    }

    public function getPickupLocation(): string
    {
        return $this->pickupLocation;
    }

    public function getPickup_location(): string
    {
        return $this->pickupLocation;
    }

    public function setPickupLocation(string $pickupLocation): static
    {
        $this->pickupLocation = $pickupLocation;

        return $this;
    }

    public function getReturnLocation(): string
    {
        return $this->returnLocation;
    }

    public function getReturn_location(): string
    {
        return $this->returnLocation;
    }

    public function setReturnLocation(string $returnLocation): static
    {
        $this->returnLocation = $returnLocation;

        return $this;
    }

    public function getDriverAge(): string
    {
        return $this->driverAge;
    }

    public function getDriver_age(): string
    {
        return $this->driverAge;
    }

    public function setDriverAge(string $driverAge): static
    {
        $this->driverAge = $driverAge;

        return $this;
    }

    public function getDays(): int
    {
        return $this->days;
    }

    public function setDays(int $days): static
    {
        $this->days = $days;

        return $this;
    }

    public function isDelivery(): bool
    {
        return $this->delivery;
    }

    public function getDelivery(): bool
    {
        return $this->delivery;
    }

    public function setDelivery(bool $delivery): static
    {
        $this->delivery = $delivery;

        return $this;
    }

    public function getDiscountCode(): ?string
    {
        return $this->discountCode;
    }

    public function getDiscount_code(): ?string
    {
        return $this->discountCode;
    }

    public function setDiscountCode(?string $discountCode): static
    {
        $this->discountCode = $discountCode;

        return $this;
    }

    public function getSubtotal(): int
    {
        return $this->subtotal;
    }

    public function setSubtotal(int $subtotal): static
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    public function getDiscount(): int
    {
        return $this->discount;
    }

    public function setDiscount(int $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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
}
