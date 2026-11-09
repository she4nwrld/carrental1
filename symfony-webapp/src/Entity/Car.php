<?php

namespace App\Entity;

use App\Repository\CarRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarRepository::class)]
class Car
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 40)]
    private string $type = 'Sedan';

    #[ORM\Column]
    private int $price = 2200;

    #[ORM\Column(length: 20)]
    private string $gear = 'Manual';

    #[ORM\Column]
    private int $seats = 5;

    #[ORM\Column]
    private int $doors = 5;

    #[ORM\Column(name: 'bag_large')]
    private int $bagLarge = 2;

    #[ORM\Column(name: 'bag_small')]
    private int $bagSmall = 2;

    #[ORM\Column]
    private int $kids = 1;

    #[ORM\Column]
    private bool $aircon = true;

    #[ORM\Column(length: 150)]
    private string $img = 'images/toyota-vios.png';

    #[ORM\Column]
    private bool $available = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getGear(): string
    {
        return $this->gear;
    }

    public function setGear(string $gear): static
    {
        $this->gear = $gear;

        return $this;
    }

    public function getSeats(): int
    {
        return $this->seats;
    }

    public function setSeats(int $seats): static
    {
        $this->seats = $seats;

        return $this;
    }

    public function getDoors(): int
    {
        return $this->doors;
    }

    public function setDoors(int $doors): static
    {
        $this->doors = $doors;

        return $this;
    }

    public function getBagLarge(): int
    {
        return $this->bagLarge;
    }

    public function getBag_large(): int
    {
        return $this->bagLarge;
    }

    public function setBagLarge(int $bagLarge): static
    {
        $this->bagLarge = $bagLarge;

        return $this;
    }

    public function getBagSmall(): int
    {
        return $this->bagSmall;
    }

    public function getBag_small(): int
    {
        return $this->bagSmall;
    }

    public function setBagSmall(int $bagSmall): static
    {
        $this->bagSmall = $bagSmall;

        return $this;
    }

    public function getKids(): int
    {
        return $this->kids;
    }

    public function setKids(int $kids): static
    {
        $this->kids = $kids;

        return $this;
    }

    public function isAircon(): bool
    {
        return $this->aircon;
    }

    public function getAircon(): bool
    {
        return $this->aircon;
    }

    public function setAircon(bool $aircon): static
    {
        $this->aircon = $aircon;

        return $this;
    }

    public function getImg(): string
    {
        return $this->img;
    }

    public function setImg(string $img): static
    {
        $this->img = $img;

        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function getAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): static
    {
        $this->available = $available;

        return $this;
    }
}
