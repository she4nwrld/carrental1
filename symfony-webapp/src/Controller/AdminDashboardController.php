<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use App\Repository\CarRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminDashboardController extends AbstractController
{
    private const STATUSES = ['all', 'pending', 'confirmed', 'overdue', 'completed', 'cancelled'];

    private const PICKUPS = PublicController::PICKUPS;

    private const AGES = PublicController::AGES;

    #[Route('/admin/dashboard', name: 'admin_dashboard', methods: ['GET', 'POST'])]
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        BookingRepository $bookingRepository,
        CarRepository $carRepository,
        MessageRepository $messageRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em,
    ): Response {
        $today = date('Y-m-d');
        $filter = (string) $request->query->get('status', 'all');
        if (!\in_array($filter, self::STATUSES, true)) {
            $filter = 'all';
        }

        $dbReady = $carRepository->ensureSeeded();

        if ($dbReady && $request->isMethod('POST')) {
            $action = (string) $request->request->get('action', '');
            $bookingId = (int) $request->request->get('booking_id', 0);
            $booking = $bookingId > 0 ? $bookingRepository->find($bookingId) : null;

            if (null !== $booking) {
                if ('delete' === $action) {
                    $em->remove($booking);
                    $em->flush();
                    $this->addFlash('success', 'Booking deleted.');
                } elseif ('edit' === $action) {
                    $pDate = (string) $request->request->get('pickup_date', $booking->getPickupDate());
                    $rDate = (string) $request->request->get('return_date', $booking->getReturnDate());
                    $pLoc = (string) $request->request->get('pickup_location', $booking->getPickupLocation());
                    $rLoc = (string) $request->request->get('return_location', $booking->getReturnLocation());
                    $age = (string) $request->request->get('driver_age', $booking->getDriverAge());
                    $st = (string) $request->request->get('status', $booking->getStatus());

                    $days = max(1, (int) round((strtotime($rDate) - strtotime($pDate)) / 86400));
                    $car = $carRepository->find($booking->getCarId());
                    $rate = $car ? $car->getPrice() : (int) round($booking->getSubtotal() / max(1, $booking->getDays()));
                    $sub = $rate * $days;
                    $delivFee = $booking->isDelivery() ? 500 : 0;
                    $disc = min($sub, $booking->getDiscount());

                    $booking
                        ->setPickupDate($pDate)
                        ->setReturnDate($rDate)
                        ->setPickupLocation($pLoc)
                        ->setReturnLocation($rLoc)
                        ->setDriverAge($age)
                        ->setDays($days)
                        ->setSubtotal($sub)
                        ->setTotal(max(0, $sub - $disc) + $delivFee)
                        ->setStatus($st);

                    $em->flush();
                    $this->addFlash('success', 'Booking details saved.');
                } elseif ($request->request->has('status')) {
                    $newStatus = (string) $request->request->get('status');
                    $booking->setStatus($newStatus);
                    $em->flush();
                    $this->addFlash('success', 'Booking status updated.');
                }
            }

            return $this->redirectToRoute('admin_dashboard', ['status' => $filter]);
        }

        if ($dbReady) {
            $allEntities = $bookingRepository->findBy([], ['id' => 'DESC']);
            $all = array_map(static fn ($b) => [
                'id' => $b->getId(),
                'car_name' => $b->getCarName(),
                'car_img' => $b->getCarImg(),
                'full_name' => $b->getFullName(),
                'email' => $b->getEmail(),
                'phone' => $b->getPhone(),
                'pickup_date' => $b->getPickupDate(),
                'return_date' => $b->getReturnDate(),
                'pickup_location' => $b->getPickupLocation(),
                'return_location' => $b->getReturnLocation(),
                'driver_age' => $b->getDriverAge(),
                'days' => $b->getDays(),
                'delivery' => $b->isDelivery(),
                'discount_code' => $b->getDiscountCode(),
                'subtotal' => $b->getSubtotal(),
                'discount' => $b->getDiscount(),
                'total' => $b->getTotal(),
                'status' => $b->getStatus(),
                'created_at' => $b->getCreatedAt(),
            ], $allEntities);
            $unread = $messageRepository->count(['isRead' => 0]);
            $allUsers = $userRepository->findAll();
            $totalUsers = \count($allUsers);
            $totalStaff = \count(array_filter($allUsers, static fn ($u): bool => $u->isStaff()));
            $totalRecords = $carRepository->count([]) + \count($allEntities);
        } else {
            $all = $this->sampleBookings($today);
            $unread = 2;
            $totalUsers = 8;
            $totalStaff = 1;
            $totalRecords = 18;
        }

        $stats = [
            'active' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'overdue' => 0,
            'revenue' => 0,
            'total_users' => $totalUsers,
            'total_staff' => $totalStaff,
            'total_records' => $totalRecords,
        ];

        foreach ($all as $bk) {
            $st = $bk['status'];
            if (isset($stats[$st])) {
                ++$stats[$st];
            }
            if ('cancelled' !== $st) {
                ++$stats['active'];
            }
            if ('confirmed' === $st || 'completed' === $st) {
                $stats['revenue'] += $bk['total'];
            }
            if ('confirmed' === $st && $bk['return_date'] < $today) {
                ++$stats['overdue'];
            }
        }

        $bookings = array_values(array_filter(
            $all,
            static function (array $bk) use ($filter, $today): bool {
                return match ($filter) {
                    'all' => true,
                    'overdue' => 'confirmed' === $bk['status'] && $bk['return_date'] < $today,
                    default => $bk['status'] === $filter,
                };
            }
        ));

        return $this->render('admin_dashboard/index.html.twig', [
            'stats' => $stats,
            'statuses' => self::STATUSES,
            'filter' => $filter,
            'bookings' => $bookings,
            'pickups' => self::PICKUPS,
            'ages' => self::AGES,
            'today' => $today,
            'unread' => $unread,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sampleBookings(string $today): array
    {
        return [
            [
                'id' => 1,
                'car_name' => 'Toyota Vios',
                'car_img' => 'images/toyota-vios.png',
                'full_name' => 'Sheene Prestin',
                'email' => 'sheene@customer.com',
                'phone' => '09175550143',
                'pickup_date' => $today,
                'return_date' => '2026-11-10',
                'pickup_location' => 'Rizal Boulevard, Dumaguete',
                'return_location' => 'Rizal Boulevard, Dumaguete',
                'driver_age' => '25-29',
                'days' => 3,
                'delivery' => false,
                'discount_code' => 'EARLY10',
                'subtotal' => 6600,
                'discount' => 660,
                'total' => 5940,
                'status' => 'pending',
                'created_at' => '2026-11-06 14:22:00',
            ],
        ];
    }
}
