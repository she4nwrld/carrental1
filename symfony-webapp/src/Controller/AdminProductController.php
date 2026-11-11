<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\CarRepository;
use App\Repository\MaintenanceRecordRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STAFF')]
class AdminProductController extends AbstractController
{
    private const VIEWS = ['all', 'available', 'hidden', 'mine'];

    /** The public filter categories, minus the "All" pill. */
    private const TYPES = ['Hatchback', 'Sedan', 'SUV', 'MPV'];

    #[Route('/admin/products', name: 'admin_product', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        CarRepository $carRepository,
        BookingRepository $bookingRepository,
        MaintenanceRecordRepository $maintenanceRepository,
        MessageRepository $messageRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em,
    ): Response {
        $filter = (string) $request->query->get('view', 'all');
        if (!\in_array($filter, self::VIEWS, true)) {
            $filter = 'all';
        }
        $term = trim((string) $request->query->get('q', ''));

        $dbReady = $carRepository->ensureSeeded();
        $user = $this->getUser();
        $currentUserId = $user instanceof User ? $user->getId() : null;
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if ($dbReady && $request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_product_action', (string) $request->request->get('_token'))) {
                $this->addFlash('warning', 'Invalid security token. Please try again.');

                return $this->redirectToRoute('admin_product', ['view' => $filter]);
            }

            $action = (string) $request->request->get('action', '');
            $carId = (int) $request->request->get('car_id', 0);
            $viewRedirect = (string) $request->request->get('view', $filter);

            if (\in_array($action, ['add', 'add_car'], true)) {
                $name = trim((string) $request->request->get('name', ''));
                $price = (int) $request->request->get('price', 0);

                if ('' === $name || $price < 1) {
                    $this->addFlash('warning', 'Please provide a valid vehicle name and daily rate.');

                    return $this->redirectToRoute('admin_product', ['view' => $viewRedirect]);
                }

                $type = (string) $request->request->get('type', 'Sedan');
                $car = (new Car())
                    ->setName($name)
                    ->setType($type)
                    ->setPrice(max(1, $price))
                    ->setGear((string) $request->request->get('gear', 'Manual'))
                    ->setSeats(max(1, (int) $request->request->get('seats', 5)))
                    ->setDoors(max(1, (int) $request->request->get('doors', 5)))
                    ->setBagLarge(max(0, (int) $request->request->get('bag_large', 2)))
                    ->setBagSmall(max(0, (int) $request->request->get('bag_small', 2)))
                    ->setKids(max(0, (int) $request->request->get('kids', 1)))
                    ->setAircon((bool) $request->request->get('aircon', 1))
                    ->setImg((string) $request->request->get('img', 'images/toyota-vios.png'))
                    ->setAvailable(true)
                    ->setCreatedBy($currentUserId);
                $em->persist($car);
                $em->flush();
                $this->addFlash('success', 'Vehicle added to the fleet.');
            } elseif ($carId > 0 && ($car = $carRepository->find($carId))) {
                // admin makausab bisan unsa, staff ang iyaha ra nga gi-add
                $canModify = $isAdmin || (null !== $currentUserId && $car->getCreatedBy() === $currentUserId);

                if (!$canModify) {
                    $this->addFlash('warning', 'Staff can only edit or delete vehicles they created.');

                    return $this->redirectToRoute('admin_product', ['view' => $viewRedirect]);
                }

                if (\in_array($action, ['edit', 'edit_car'], true)) {
                    $car
                        ->setName((string) $request->request->get('name', $car->getName()))
                        ->setType((string) $request->request->get('type', $car->getType()))
                        ->setPrice(max(1, (int) $request->request->get('price', $car->getPrice())))
                        ->setGear((string) $request->request->get('gear', $car->getGear()))
                        ->setSeats(max(1, (int) $request->request->get('seats', $car->getSeats())))
                        ->setDoors(max(1, (int) $request->request->get('doors', $car->getDoors())))
                        ->setBagLarge(max(0, (int) $request->request->get('bag_large', $car->getBagLarge())))
                        ->setBagSmall(max(0, (int) $request->request->get('bag_small', $car->getBagSmall())))
                        ->setKids(max(0, (int) $request->request->get('kids', $car->getKids())))
                        ->setAircon((bool) $request->request->get('aircon', $car->isAircon()))
                        ->setImg((string) $request->request->get('img', $car->getImg()));
                    $em->flush();
                    $this->addFlash('success', 'Vehicle updated.');
                } elseif (\in_array($action, ['toggle', 'toggle_car'], true) || $request->request->has('available')) {
                    $avail = '1' === (string) $request->request->get('available', '0');
                    $car->setAvailable($avail);
                    $em->flush();
                    $this->addFlash('success', 'Availability changed.');
                } elseif (\in_array($action, ['delete', 'drop_car'], true)) {
                    $em->remove($car);
                    $em->flush();
                    $this->addFlash('success', 'Vehicle deleted.');
                }
            }

            return $this->redirectToRoute('admin_product', ['view' => $viewRedirect]);
        }

        if ($dbReady) {
            $allCarEntities = $carRepository->findBy([], ['type' => 'ASC', 'name' => 'ASC']);
            $allBookings = $bookingRepository->findAll();
            $openRepairs = $maintenanceRepository->findBy(['resolved' => false]);
            $usersById = [];
            foreach ($userRepository->findAll() as $u) {
                $usersById[$u->getId()] = $u->getFullName();
            }

            $populateMeta = static function (Car $c) use ($allBookings, $openRepairs, $usersById, $currentUserId, $isAdmin): array {
                $totalBk = 0;
                $liveBk = 0;
                foreach ($allBookings as $b) {
                    if ($b->getCarId() === $c->getId()) {
                        ++$totalBk;
                        if (\in_array($b->getStatus(), ['pending', 'confirmed'], true)) {
                            ++$liveBk;
                        }
                    }
                }
                $repNote = null;
                $repUntil = null;
                foreach ($openRepairs as $r) {
                    if ($r->getCarId() === $c->getId()) {
                        $repNote = $r->getDescription();
                        $repUntil = $r->getEndDate();
                        break;
                    }
                }

                $creatorId = $c->getCreatedBy();
                $isMine = null !== $currentUserId && $creatorId === $currentUserId;
                $ownerName = null !== $creatorId && isset($usersById[$creatorId]) ? $usersById[$creatorId] : 'Admin';

                return [
                    'id' => $c->getId(),
                    'name' => $c->getName(),
                    'type' => $c->getType(),
                    'price' => $c->getPrice(),
                    'gear' => $c->getGear(),
                    'seats' => $c->getSeats(),
                    'doors' => $c->getDoors(),
                    'bag_large' => $c->getBagLarge(),
                    'bag_small' => $c->getBagSmall(),
                    'kids' => $c->getKids(),
                    'aircon' => $c->isAircon() ? 1 : 0,
                    'img' => $c->getImg(),
                    'available' => $c->isAvailable() ? 1 : 0,
                    'bookings' => $totalBk,
                    'live' => $liveBk,
                    'repair_note' => $repNote,
                    'repair_until' => $repUntil,
                    'created_by' => $creatorId,
                    'owner_name' => $isMine ? 'You (' . $ownerName . ')' : $ownerName,
                    'is_mine' => $isMine,
                    'can_edit' => $isAdmin || $isMine,
                ];
            };

            $all = array_map($populateMeta, $allCarEntities);
            $unread = $messageRepository->count(['isRead' => 0]);
        } else {
            $all = $this->sampleCars($currentUserId, $isAdmin);
            $unread = 2;
        }

        $total = \count($all);
        $liveCount = 0;
        $priceSum = 0;

        foreach ($all as $car) {
            if (1 === (int) $car['available']) {
                ++$liveCount;
            }
            $priceSum += $car['price'];
        }

        $stats = [
            'total' => $total,
            'live' => $liveCount,
            'off' => $total - $liveCount,
            'avg_price' => $total > 0 ? (int) round($priceSum / $total) : 0,
        ];

        $cars = array_values(array_filter(
            $all,
            static function (array $car) use ($filter, $term): bool {
                $matchesTab = match ($filter) {
                    'available' => 1 === (int) $car['available'],
                    'hidden' => 0 === (int) $car['available'],
                    'mine' => !empty($car['is_mine']),
                    default => true,
                };
                if (!$matchesTab) {
                    return false;
                }
                if ('' === $term) {
                    return true;
                }
                $haystack = strtolower($car['name'] . ' ' . $car['type'] . ' ' . $car['gear'] . ' ' . ($car['owner_name'] ?? ''));

                return str_contains($haystack, strtolower($term));
            }
        ));

        return $this->render('admin_product/index.html.twig', [
            'stats' => $stats,
            'views' => self::VIEWS,
            'filter' => $filter,
            'term' => $term,
            'types' => self::TYPES,
            'cars' => $cars,
            'unread' => $unread,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sampleCars(?int $currentUserId = null, bool $isAdmin = false): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Toyota Avanza',
                'type' => 'MPV',
                'price' => 2800,
                'gear' => 'Manual',
                'seats' => 7,
                'doors' => 5,
                'bag_large' => 2,
                'bag_small' => 3,
                'kids' => 3,
                'aircon' => 1,
                'img' => 'images/toyota-avanza.png',
                'available' => 1,
                'bookings' => 1,
                'live' => 0,
                'repair_note' => null,
                'repair_until' => null,
                'created_by' => 1,
                'owner_name' => 1 === $currentUserId ? 'You (Sheene Admin)' : 'Sheene Admin',
                'is_mine' => 1 === $currentUserId,
                'can_edit' => $isAdmin || 1 === $currentUserId,
            ],
            [
                'id' => 2,
                'name' => 'Kia Picanto',
                'type' => 'Hatchback',
                'price' => 1500,
                'gear' => 'Manual',
                'seats' => 5,
                'doors' => 5,
                'bag_large' => 1,
                'bag_small' => 2,
                'kids' => 1,
                'aircon' => 1,
                'img' => 'images/kia-picanto.png',
                'available' => 1,
                'bookings' => 0,
                'live' => 0,
                'repair_note' => null,
                'repair_until' => null,
                'created_by' => 2,
                'owner_name' => 2 === $currentUserId ? 'You (Jomar Staff)' : 'Jomar Staff',
                'is_mine' => 2 === $currentUserId,
                'can_edit' => $isAdmin || 2 === $currentUserId,
            ],
        ];
    }
}
