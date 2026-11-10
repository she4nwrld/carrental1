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
    private const VIEWS = ['all', 'booked', 'new', 'disabled'];

    private const LABELS = [
        'all' => 'All',
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
            $action = (string) $request->request->get('action', '');
            $userId = (int) $request->request->get('user_id', 0);
            $viewRedirect = (string) $request->request->get('view', $filter);
            $u = $userId > 0 ? $userRepository->find($userId) : null;

            if (null !== $u) {
                if ('delete' === $action) {
                    $em->remove($u);
                    $em->flush();
                    $this->addFlash('success', sprintf('Customer "%s" deleted.', $u->getFullName()));
                } elseif ('reset_password' === $action) {
                    $tempPass = 'Shift-' . random_int(1000, 9999);
                    $u->setPassword($passwordHasher->hashPassword($u, $tempPass));
                    $em->flush();
                    $this->addFlash('temp_password', $tempPass);
                } elseif ($request->request->has('is_active')) {
                    $act = $request->request->getInt('is_active', 1);
                    $u->setIsActive($act);
                    $em->flush();
                    $this->addFlash('success', sprintf('Customer "%s" %s.', $u->getFullName(), $act ? 'enabled' : 'disabled'));
                }
            }

            return $this->redirectToRoute('admin_customer', ['view' => $viewRedirect]);
        }

        if ($dbReady) {
            $allUsers = array_values(array_filter(
                $userRepository->findAll(),
                static fn (User $u): bool => !$u->isAdmin()
            ));
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
            'fresh' => 0,
            'buyers' => 0,
            'disabled' => 0,
            'revenue' => 0,
        ];

        foreach ($all as $u) {
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
                $matchesView = match ($filter) {
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
                $haystack = strtolower($u['full_name'] . ' ' . $u['email'] . ' ' . $u['phone']);

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
                'id' => 2,
                'full_name' => 'Angela Reyes',
                'email' => 'angela.reyes@yahoo.com',
                'phone' => '0918 330 4821',
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
