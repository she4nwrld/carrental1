<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
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
            } catch (\Throwable) {
            }
        }
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
        foreach ($this->defaultOfflineUsers() as $u) {
            if (0 === strcasecmp((string) $u->getUsername(), $identifier) || 0 === strcasecmp((string) $u->getEmail(), $identifier)) {
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

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        $reloaded = $this->findUserFlexible($user->getUserIdentifier());

        return $reloaded ?? $user;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    /**
     * @var list<User>|null
     */
    private static ?array $offlineUsers = null;

    /**
     * @return list<User>
     */
    private function defaultOfflineUsers(): array
    {
        if (null !== self::$offlineUsers) {
            return self::$offlineUsers;
        }

        return self::$offlineUsers = [
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
        ];
    }
}
