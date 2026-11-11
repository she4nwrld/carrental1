<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(name: 'full_name', length: 120)]
    private string $fullName = '';

    #[ORM\Column(length: 40)]
    private string $phone = '';

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(name: 'is_active')]
    private int $isActive = 1;

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

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        if (null === $this->email || '' === $this->email) {
            $this->email = str_contains($username, '@') ? $username : $username . '@shift.ph';
        }
        if ('' === $this->fullName) {
            $this->fullName = ucfirst(explode('@', $username)[0]);
        }

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        if (null === $this->username || '' === $this->username) {
            $this->username = $email;
        }

        return $this;
    }

    public function getFullName(): string
    {
        return '' !== $this->fullName ? $this->fullName : (string) $this->username;
    }

    public function getFull_name(): string
    {
        return $this->getFullName();
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

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

    public function getUserIdentifier(): string
    {
        return (string) ($this->username ?: $this->email);
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function isAdmin(): bool
    {
        return \in_array('ROLE_ADMIN', $this->getRoles(), true);
    }

    public function isStaff(): bool
    {
        return \in_array('ROLE_STAFF', $this->getRoles(), true);
    }

    public function getRole(): string
    {
        if ($this->isAdmin()) {
            return 'admin';
        }
        if ($this->isStaff()) {
            return 'staff';
        }

        return 'customer';
    }

    public function setRole(string $role): static
    {
        if ('admin' === $role) {
            $this->roles = ['ROLE_ADMIN', 'ROLE_USER'];
        } elseif ('staff' === $role) {
            $this->roles = ['ROLE_STAFF', 'ROLE_USER'];
        } else {
            $this->roles = ['ROLE_USER'];
        }

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getIsActive(): int
    {
        return $this->isActive;
    }

    public function getIs_active(): int
    {
        return $this->isActive;
    }

    public function setIsActive(int $isActive): static
    {
        $this->isActive = $isActive;

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

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', (string) $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}
