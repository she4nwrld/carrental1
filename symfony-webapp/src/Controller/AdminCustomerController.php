<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\CarRepository;
use App\Repository\MessageRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminCustomerController extends AbstractController
{
    private const VIEWS = ['all', 'staff', 'admin', 'booked', 'new', 'disabled'];

    private const LABELS = [
        'all' => 'All users',
        'staff' => 'Staff',
        'admin' => 'Admins',
        'booked' => 'Have booked',
        'new' => 'No bookings yet',
        'disabled' => 'Disabled',
    ];

    #[Route('/admin/customers', name: 'admin_customer', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
        CarRepository $carRepository,
        BookingRepository $bookingRepository,
        ReviewRepository $reviewRepository,
        MessageRepository $messageRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): Response {
        $filter = (string) $request->query->get('view', 'all');
        if (!\in_array($filter, self::VIEWS, true)) {
            $filter = 'all';
        }
        $term = trim((string) $request->query->get('q', ''));

        $dbReady = $carRepository->ensureSeeded();

        if ($dbReady && $request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_user_action', (string) $request->request->get('_token'))) {
                $this->addFlash('warning', 'Invalid security token. Please try again.');

                return $this->redirectToRoute('admin_customer', ['view' => $filter]);
            }

            $action = (string) $request->request->get('action', '');
            $userId = (int) $request->request->get('user_id', 0);
            $viewRedirect = (string) $request->request->get('view', $filter);

            // himo ug bag-o nga user/staff/admin account
            if ('create_user' === $action) {
                $fullName = trim((string) $request->request->get('full_name', ''));
                $email = trim((string) $request->request->get('email', ''));
                $phone = trim((string) $request->request->get('phone', ''));
                $role = (string) $request->request->get('role', 'staff');
                $plainPass = (string) $request->request->get('password', '');

                if ('' === $fullName || '' === $email || !filter_var($email, \FILTER_VALIDATE_EMAIL)) {
                    $this->addFlash('warning', 'Please provide a valid full name and email address.');

                    return $this->redirectToRoute('admin_customer', ['view' => $viewRedirect]);
                }

                if (\strlen($plainPass) < 6) {
                    $this->addFlash('warning', 'Password must be at least 6 characters.');

                    return $this->redirectToRoute('admin_customer', ['view' => $viewRedirect]);
                }

                if (null !== $userRepository->findOneBy(['username' => $email]) || null !== $userRepository->findOneBy(['email' => $email])) {
                    $this->addFlash('warning', 'An account with that email address already exists.');

                    return $this->redirectToRoute('admin_customer', ['view' => $viewRedirect]);
                }

                if (!\in_array($role, ['admin', 'staff', 'customer'], true)) {
                    $role = 'staff';
                }

                $newUser = (new User())
                    ->setFullName($fullName)
                    ->setEmail($email)
                    ->setUsername($email)
                    ->setPhone('' !== $phone ? $phone : '09170000000')
                    ->setRole($role)
                    ->setIsActive(1)
                    ->setCreatedAt(date('Y-m-d H:i:s'));
                $newUser->setPassword($passwordHasher->hashPassword($newUser, $plainPass));

                $em->persist($newUser);
                $em->flush();

                $this->addFlash('success', sprintf('%s account "%s" created.', ucfirst($role), $fullName));

                return $this->redirectToRoute('admin_customer', ['view' => $viewRedirect]);
            }

            $u = $userId > 0 ? $userRepository->find($userId) : null;

            if (null !== $u) {
                if ('edit_user' === $action) {
                    $fullName = trim((string) $request->request->get('full_name', $u->getFullName()));
                    $email = trim((string) $request->request->get('email', (string) $u->getEmail()));
                    $phone = trim((string) $request->request->get('phone', $u->getPhone()));
                    $role = (string) $request->request->get('role', $u->getRole());

                    if ('' === $fullName || '' === $email || !filter_var($email, \FILTER_VALIDATE_EMAIL)) {
                        $this->addFlash('warning', 'Name and a valid email address are required.');

                        return $this->redirectToRoute('admin_customer', ['view' => $viewRedirect]);
                    }

                    $dup = $userRepository->findOneBy(['email' => $email]);
                    if (null !== $dup && $dup->getId() !== $u->getId()) {
                        $this->addFlash('warning', 'That email address is already in use by another account.');

                        return $this->redirectToRoute('admin_customer', ['view' => $viewRedirect]);
                    }

                    if (\in_array($role, ['admin', 'staff', 'customer'], true)) {
                        $u->setRole($role);
                    }

                    $u->setFullName($fullName)
                        ->setEmail($email)
                        ->setUsername($email)
                        ->setPhone($phone);

                    $em->flush();
                    $this->addFlash('success', sprintf('Account "%s" updated.', $u->getFullName()));
                } elseif ('delete' === $action) {
                    $currentUser = $this->getUser();
                    if ($currentUser instanceof User && $currentUser->getId() === $u->getId()) {
                        $this->addFlash('warning', 'You cannot delete your own active admin account.');
                    } else {
                        $em->remove($u);
                        $em->flush();
                        $this->addFlash('success', sprintf('Account "%s" deleted.', $u->getFullName()));
                    }
                } elseif ('reset_password' === $action) {
                    $tempPass = 'Shift-' . random_int(1000, 9999);
                    $u->setPassword($passwordHasher->hashPassword($u, $tempPass));
                    $em->flush();
                    $this->addFlash('temp_password', $tempPass);
                } elseif ($request->request->has('is_active')) {
                    $act = $request->request->getInt('is_active', 1);
                    $u->setIsActive($act);
                    $em->flush();
                    $this->addFlash('success', sprintf('Account "%s" %s.', $u->getFullName(), $act ? 'enabled' : 'disabled'));
                }
            }

            return $this->redirectToRoute('admin_customer', ['view' => $viewRedirect]);
        }

        if ($dbReady) {
            $allUsers = $userRepository->findAll();
            $allBookings = $bookingRepository->findAll();
            $allReviews = $reviewRepository->findAll();

            $all = array_map(static function (User $u) use ($allBookings, $allReviews): array {
                $bkCount = 0;
                $liveCount = 0;
                $doneCount = 0;
                $spent = 0;
                $lastBooked = null;

                foreach ($allBookings as $b) {
                    if ($b->getUserId() === $u->getId() || strtolower($b->getEmail()) === strtolower((string) $u->getEmail())) {
                        ++$bkCount;
                        if (\in_array($b->getStatus(), ['pending', 'confirmed'], true)) {
                            ++$liveCount;
                        }
                        if ('completed' === $b->getStatus()) {
                            ++$doneCount;
                        }
                        if (\in_array($b->getStatus(), ['confirmed', 'completed'], true)) {
                            $spent += $b->getTotal();
                        }
                        if (null === $lastBooked || $b->getCreatedAt() > $lastBooked) {
                            $lastBooked = substr($b->getCreatedAt(), 0, 10);
                        }
                    }
                }

                $revCount = 0;
                foreach ($allReviews as $r) {
                    if ($r->getUserId() === $u->getId() || strtolower($r->getEmail()) === strtolower((string) $u->getEmail())) {
                        ++$revCount;
                    }
                }

                return [
                    'id' => $u->getId(),
                    'full_name' => $u->getFullName(),
                    'email' => (string) $u->getEmail(),
                    'phone' => $u->getPhone(),
                    'role' => $u->getRole(),
                    'is_active' => $u->getIsActive(),
                    'created_at' => $u->getCreatedAt(),
                    'bookings' => $bkCount,
                    'live' => $liveCount,
                    'done' => $doneCount,
                    'reviews' => $revCount,
                    'spent' => $spent,
                    'last_booked' => $lastBooked,
                ];
            }, $allUsers);

            usort($all, static fn (array $a, array $b): int => $b['spent'] <=> $a['spent']);
            $unread = $messageRepository->count(['isRead' => 0]);
        } else {
            $all = $this->sampleCustomers();
            $unread = 2;
        }

        $thisMonth = date('Y-m');
        $stats = [
            'total' => \count($all),
            'staff' => 0,
            'admins' => 0,
            'fresh' => 0,
            'buyers' => 0,
            'disabled' => 0,
            'revenue' => 0,
        ];

        foreach ($all as $u) {
            if ('staff' === ($u['role'] ?? 'customer')) {
                ++$stats['staff'];
            } elseif ('admin' === ($u['role'] ?? 'customer')) {
                ++$stats['admins'];
            }
            if (str_starts_with($u['created_at'], $thisMonth)) {
                ++$stats['fresh'];
            }
            if ($u['bookings'] > 0) {
                ++$stats['buyers'];
            }
            if (0 === $u['is_active']) {
                ++$stats['disabled'];
            }
            $stats['revenue'] += $u['spent'];
        }

        $customers = array_values(array_filter(
            $all,
            static function (array $u) use ($filter, $term): bool {
                $role = $u['role'] ?? 'customer';
                $matchesView = match ($filter) {
                    'staff' => 'staff' === $role,
                    'admin' => 'admin' === $role,
                    'booked' => $u['bookings'] > 0,
                    'new' => 0 === $u['bookings'],
                    'disabled' => 0 === $u['is_active'],
                    default => true,
                };
                if (!$matchesView) {
                    return false;
                }
                if ('' === $term) {
                    return true;
                }
                $haystack = strtolower($u['full_name'] . ' ' . $u['email'] . ' ' . $u['phone'] . ' ' . $role);

                return str_contains($haystack, strtolower($term));
            }
        ));

        return $this->render('admin_customer/index.html.twig', [
            'stats' => $stats,
            'views' => self::VIEWS,
            'labels' => self::LABELS,
            'filter' => $filter,
            'term' => $term,
            'customers' => $customers,
            'unread' => $unread,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sampleCustomers(): array
    {
        return [
            [
                'id' => 1,
                'full_name' => 'Sheene Admin',
                'email' => 'sheene@admin.com',
                'phone' => '0917 123 4567',
                'role' => 'admin',
                'is_active' => 1,
                'created_at' => '2026-08-01 09:00:00',
                'bookings' => 0,
                'live' => 0,
                'done' => 0,
                'reviews' => 0,
                'spent' => 0,
                'last_booked' => null,
            ],
            [
                'id' => 2,
                'full_name' => 'Jomar Staff',
                'email' => 'staff@shift.ph',
                'phone' => '0918 221 7744',
                'role' => 'staff',
                'is_active' => 1,
                'created_at' => '2026-08-05 09:00:00',
                'bookings' => 0,
                'live' => 0,
                'done' => 0,
                'reviews' => 0,
                'spent' => 0,
                'last_booked' => null,
            ],
            [
                'id' => 3,
                'full_name' => 'Sheene Customer',
                'email' => 'sheene@customer.com',
                'phone' => '0917 987 6543',
                'role' => 'customer',
                'is_active' => 1,
                'created_at' => '2026-08-10 09:00:00',
                'bookings' => 2,
                'live' => 1,
                'done' => 1,
                'reviews' => 1,
                'spent' => 14400,
                'last_booked' => '2026-10-29',
            ],
            [
                'id' => 4,
                'full_name' => 'Angela Reyes',
                'email' => 'angela.reyes@yahoo.com',
                'phone' => '0918 330 4821',
                'role' => 'customer',
                'is_active' => 1,
                'created_at' => '2026-08-14 09:15:00',
                'bookings' => 4,
                'live' => 1,
                'done' => 3,
                'reviews' => 2,
                'spent' => 38400,
                'last_booked' => '2026-10-29',
            ],
        ];
    }
}
