<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @extends ServiceEntityRepository<User>
 * @implements UserProviderInterface<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface, UserProviderInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private CarRepository $carRepository,
    ) {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        if ($this->carRepository->ensureSeeded()) {
            try {
                $this->getEntityManager()->persist($user);
                $this->getEntityManager()->flush();
                return;
            } catch (\Throwable) {
            }
        }

        $this->saveOfflineUser($user);
    }

    public function findUserFlexible(string $identifier): ?User
    {
        if ($this->carRepository->ensureSeeded()) {
            try {
                $user = $this->findOneBy(['username' => $identifier])
                    ?? $this->findOneBy(['email' => $identifier]);
                if ($user instanceof User) {
                    return $user;
                }
            } catch (\Throwable) {
            }
        }

        // fallback accounts kung offline ang Docker MySQL
        foreach ($this->getOfflineUsers() as $u) {
            if (0 === strcasecmp((string) $u->getUsername(), $identifier) || 0 === strcasecmp((string) $u->getEmail(), $identifier)) {
                return $u;
            }
        }

        return null;
    }

    public function findOfflineById(int $id): ?User
    {
        foreach ($this->getOfflineUsers() as $u) {
            if ($u->getId() === $id) {
                return $u;
            }
        }

        return null;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->findUserFlexible($identifier);
        if (!$user instanceof User) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }
        if (0 === $user->getIsActive()) {
            throw new CustomUserMessageAuthenticationException('This account has been disabled. Please contact the admin.');
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        if (null !== $user->getId() && !$this->carRepository->ensureSeeded()) {
            $byId = $this->findOfflineById($user->getId());
            if ($byId instanceof User) {
                if (0 === $byId->getIsActive()) {
                    throw new CustomUserMessageAuthenticationException('This account has been disabled. Please contact the admin.');
                }

                return $byId;
            }
        }

        $reloaded = $this->findUserFlexible($user->getUserIdentifier()) ?? $user;
        if (0 === $reloaded->getIsActive()) {
            throw new CustomUserMessageAuthenticationException('This account has been disabled. Please contact the admin.');
        }

        return $reloaded;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    private function offlineStorePath(): string
    {
        return sys_get_temp_dir() . '/shift_offline_users_v1.json';
    }

    /**
     * @return list<User>
     */
    public function getOfflineUsers(): array
    {
        $path = $this->offlineStorePath();
        if (is_file($path)) {
            $raw = @file_get_contents($path);
            if (\is_string($raw) && '' !== $raw) {
                $rows = json_decode($raw, true);
                if (\is_array($rows) && !empty($rows)) {
                    $users = [];
                    foreach ($rows as $r) {
                        $u = (new User())
                            ->setId((int) ($r['id'] ?? 1))
                            ->setUsername((string) ($r['username'] ?? $r['email'] ?? ''))
                            ->setEmail((string) ($r['email'] ?? ''))
                            ->setFullName((string) ($r['full_name'] ?? ''))
                            ->setPhone((string) ($r['phone'] ?? ''))
                            ->setRole((string) ($r['role'] ?? 'customer'))
                            ->setIsActive((int) ($r['is_active'] ?? 1))
                            ->setCreatedAt((string) ($r['created_at'] ?? '2026-08-01 09:00:00'))
                            ->setPassword((string) ($r['password'] ?? ''));
                        $users[] = $u;
                    }

                    return $users;
                }
            }
        }

        $defaults = [
            (new User())
                ->setId(1)
                ->setUsername('sheene@admin.com')
                ->setEmail('sheene@admin.com')
                ->setFullName('Sheene Admin')
                ->setPhone('09171234567')
                ->setRole('admin')
                ->setIsActive(1)
                ->setCreatedAt('2026-08-01 09:00:00')
                ->setPassword(password_hash('admin123', \PASSWORD_BCRYPT, ['cost' => 4])),
            (new User())
                ->setId(2)
                ->setUsername('staff@shift.ph')
                ->setEmail('staff@shift.ph')
                ->setFullName('Jomar Staff')
                ->setPhone('09182217744')
                ->setRole('staff')
                ->setIsActive(1)
                ->setCreatedAt('2026-08-05 09:00:00')
                ->setPassword(password_hash('staff123', \PASSWORD_BCRYPT, ['cost' => 4])),
            (new User())
                ->setId(3)
                ->setUsername('sheene@customer.com')
                ->setEmail('sheene@customer.com')
                ->setFullName('Sheene Customer')
                ->setPhone('09179876543')
                ->setRole('customer')
                ->setIsActive(1)
                ->setCreatedAt('2026-08-10 09:00:00')
                ->setPassword(password_hash('customer123', \PASSWORD_BCRYPT, ['cost' => 4])),
            (new User())
                ->setId(4)
                ->setUsername('angela.reyes@yahoo.com')
                ->setEmail('angela.reyes@yahoo.com')
                ->setFullName('Angela Reyes')
                ->setPhone('09183304821')
                ->setRole('customer')
                ->setIsActive(1)
                ->setCreatedAt('2026-08-14 09:15:00')
                ->setPassword(password_hash('customer123', \PASSWORD_BCRYPT, ['cost' => 4])),
        ];

        $this->persistOfflineUsers($defaults);

        return $defaults;
    }

    /**
     * @param list<User> $users
     */
    public function persistOfflineUsers(array $users): void
    {
        $rows = [];
        foreach ($users as $u) {
            $rows[] = [
                'id' => $u->getId(),
                'username' => $u->getUsername(),
                'email' => $u->getEmail(),
                'full_name' => $u->getFullName(),
                'phone' => $u->getPhone(),
                'role' => $u->getRole(),
                'is_active' => $u->getIsActive(),
                'created_at' => $u->getCreatedAt(),
                'password' => $u->getPassword(),
            ];
        }
        @file_put_contents($this->offlineStorePath(), json_encode($rows, \JSON_PRETTY_PRINT));
    }

    public function saveOfflineUser(User $target): User
    {
        $users = $this->getOfflineUsers();
        $found = false;
        $maxId = 0;

        foreach ($users as $idx => $u) {
            if ((int) $u->getId() > $maxId) {
                $maxId = (int) $u->getId();
            }
            if (null !== $target->getId() && $u->getId() === $target->getId()) {
                $users[$idx] = $target;
                $found = true;
            }
        }

        if (!$found) {
            if (null === $target->getId() || 0 === $target->getId()) {
                $target->setId($maxId + 1);
            }
            $users[] = $target;
        }

        $this->persistOfflineUsers($users);

        return $target;
    }

    public function deleteOfflineUser(int $id): bool
    {
        $users = $this->getOfflineUsers();
        $filtered = [];
        $deleted = false;
        foreach ($users as $u) {
            if ($u->getId() === $id) {
                $deleted = true;
                continue;
            }
            $filtered[] = $u;
        }
        if ($deleted) {
            $this->persistOfflineUsers($filtered);
        }

        return $deleted;
    }
}
