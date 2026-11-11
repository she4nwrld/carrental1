<?php

namespace App\Controller;

use App\Entity\MaintenanceRecord;
use App\Repository\BookingRepository;
use App\Repository\CarRepository;
use App\Repository\MaintenanceRecordRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STAFF')]
final class AdminMaintenanceController extends AbstractController
{
    private const VIEWS = ['open', 'resolved', 'all'];

    #[Route('/admin/maintenance', name: 'admin_maintenance', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        MaintenanceRecordRepository $maintenanceRepository,
        CarRepository $carRepository,
        BookingRepository $bookingRepository,
        MessageRepository $messageRepository,
        EntityManagerInterface $em,
    ): Response {
        $filter = (string) $request->query->get('view', 'open');
        if (!\in_array($filter, self::VIEWS, true)) {
            $filter = 'open';
        }

        $dbReady = $carRepository->ensureSeeded();

        if ($dbReady && $request->isMethod('POST')) {
            $action = (string) $request->request->get('action', '');
            $repairId = (int) $request->request->get('repair_id', 0);
            $viewRedirect = (string) $request->request->get('view', $filter);

            if ('add' === $action || ($request->request->has('car_id') && !$request->request->has('repair_id'))) {
                $carId = (int) $request->request->get('car_id', 0);
                $car = $carRepository->find($carId);
                if (null !== $car) {
                    $rec = (new MaintenanceRecord())
                        ->setCarId($car->getId())
                        ->setCarName((string) $car->getName())
                        ->setCarType($car->getType())
                        ->setCarImg($car->getImg())
                        ->setCost(max(0, (int) $request->request->get('cost', 0)))
                        ->setStartDate((string) $request->request->get('start_date', date('Y-m-d')))
                        ->setEndDate((string) $request->request->get('end_date', date('Y-m-d', strtotime('+3 days'))))
                        ->setDescription((string) $request->request->get('description', 'Scheduled maintenance'))
                        ->setResolved(false);
                    $em->persist($rec);
                    $em->flush();
                    $this->addFlash('success', sprintf('Repair logged for %s.', $car->getName()));
                }
            } elseif ($repairId > 0 && ($job = $maintenanceRepository->find($repairId))) {
                if ('delete' === $action) {
                    if (!$this->isGranted('ROLE_ADMIN')) {
                        $this->addFlash('warning', 'Only Admin can delete maintenance records.');

                        return $this->redirectToRoute('admin_maintenance', ['view' => $viewRedirect]);
                    }
                    $em->remove($job);
                    $em->flush();
                    $this->addFlash('success', 'Repair record deleted.');
                } elseif ($request->request->has('resolved')) {
                    $res = '1' === (string) $request->request->get('resolved', '0');
                    $job->setResolved($res);
                    $em->flush();
                    $this->addFlash('success', sprintf('Repair #%d marked as %s.', $repairId, $res ? 'finished' : 'in the shop'));
                }
            }

            return $this->redirectToRoute('admin_maintenance', ['view' => $viewRedirect]);
        }

        if ($dbReady) {
            $allRecords = $maintenanceRepository->findBy([], ['id' => 'DESC']);
            $allBookings = $bookingRepository->findAll();
            $all = array_map(static function (MaintenanceRecord $r) use ($allBookings): array {
                $clashes = 0;
                foreach ($allBookings as $b) {
                    if ($b->getCarId() === $r->getCarId()
                        && \in_array($b->getStatus(), ['pending', 'confirmed'], true)
                        && $b->getPickupDate() <= $r->getEndDate()
                        && $b->getReturnDate() >= $r->getStartDate()
                    ) {
                        ++$clashes;
                    }
                }

                return [
                    'id' => $r->getId(),
                    'car_id' => $r->getCarId(),
                    'car_name' => $r->getCarName(),
                    'car_type' => $r->getCarType(),
                    'car_img' => $r->getCarImg(),
                    'start_date' => $r->getStartDate(),
                    'end_date' => $r->getEndDate(),
                    'cost' => $r->getCost(),
                    'description' => $r->getDescription(),
                    'resolved' => $r->isResolved(),
                    'created_at' => $r->getCreatedAt(),
                    'clashes' => $clashes,
                ];
            }, $allRecords);

            $fleet = array_map(static fn ($c) => [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'type' => $c->getType(),
            ], $carRepository->findBy([], ['name' => 'ASC']));
            $unread = $messageRepository->count(['isRead' => 0]);
        } else {
            $all = $this->sampleRepairs();
            $fleet = $this->sampleFleetList();
            $unread = 2;
        }

        $stats = [
            'open_jobs' => 0,
            'done_jobs' => 0,
            'spend' => 0,
        ];
        $offRoadUnits = [];

        foreach ($all as $job) {
            if ($job['resolved']) {
                ++$stats['done_jobs'];
            } else {
                ++$stats['open_jobs'];
                $offRoadUnits[$job['car_id']] = true;
            }
            $stats['spend'] += $job['cost'];
        }

        $repairs = array_values(array_filter(
            $all,
            static fn (array $job): bool => match ($filter) {
                'open' => false === $job['resolved'],
                'resolved' => true === $job['resolved'],
                default => true,
            }
        ));

        return $this->render('admin_maintenance/index.html.twig', [
            'stats' => $stats,
            'off_road' => \count($offRoadUnits),
            'views' => self::VIEWS,
            'filter' => $filter,
            'repairs' => $repairs,
            'fleet' => $fleet,
            'unread' => $unread,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sampleRepairs(): array
    {
        return [
            [
                'id' => 6,
                'car_id' => 5,
                'car_name' => 'Toyota Avanza',
                'car_type' => 'MPV',
                'car_img' => 'images/toyota-avanza.png',
                'start_date' => '2026-11-05',
                'end_date' => '2026-11-12',
                'cost' => 8500,
                'description' => 'AC compressor replacement and cabin filter service.',
                'resolved' => false,
                'created_at' => '2026-11-05 08:30:00',
                'clashes' => 0,
            ],
        ];
    }

    /**
     * @return list<array{id: int, name: string, type: string}>
     */
    private function sampleFleetList(): array
    {
        return [
            ['id' => 1, 'name' => 'Toyota Vios', 'type' => 'Sedan'],
            ['id' => 5, 'name' => 'Toyota Avanza', 'type' => 'MPV'],
        ];
    }
}
