<?php

namespace App\Controller;

use App\Entity\Promo;
use App\Repository\BookingRepository;
use App\Repository\CarRepository;
use App\Repository\MessageRepository;
use App\Repository\PromoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STAFF')]
final class AdminPromoController extends AbstractController
{
    private const VIEWS = ['all', 'live', 'off', 'expired'];

    #[Route('/admin/promos', name: 'admin_promo', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        PromoRepository $promoRepository,
        CarRepository $carRepository,
        BookingRepository $bookingRepository,
        MessageRepository $messageRepository,
        EntityManagerInterface $em,
    ): Response {
        $today = date('Y-m-d');
        $filter = (string) $request->query->get('view', 'all');
        if (!\in_array($filter, self::VIEWS, true)) {
            $filter = 'all';
        }

        $dbReady = $carRepository->ensureSeeded();

        if ($dbReady && $request->isMethod('POST')) {
            $action = (string) $request->request->get('action', '');
            $promoId = (int) $request->request->get('promo_id', 0);
            $viewRedirect = (string) $request->request->get('view', $filter);

            if ('add' === $action || ($request->request->has('title') && 0 === $promoId)) {
                $promo = (new Promo())
                    ->setCode($request->request->getString('code'))
                    ->setTag($request->request->getString('tag', 'Promo'))
                    ->setTitle($request->request->getString('title', 'Special Offer'))
                    ->setBlurb($request->request->getString('blurb', ''))
                    ->setPercent(max(0, min(100, $request->request->getInt('percent', 0))))
                    ->setFreeDays(max(0, $request->request->getInt('free_days', 0)))
                    ->setMinDays(max(0, $request->request->getInt('min_days', 0)))
                    ->setMinAdvanceDays(max(0, $request->request->getInt('min_advance_days', 0)))
                    ->setStackable($request->request->getInt('stackable', 0))
                    ->setExpiresAt($request->request->getString('expires_at'))
                    ->setSortOrder($request->request->getInt('sort_order', 0))
                    ->setActive(1);
                $em->persist($promo);
                $em->flush();
                $this->addFlash('success', sprintf('Promo "%s" created.', $promo->getTitle()));
            } elseif ($promoId > 0 && ($promo = $promoRepository->find($promoId))) {
                if ('delete' === $action) {
                    $em->remove($promo);
                    $em->flush();
                    $this->addFlash('success', 'Promo deleted.');
                } elseif ('edit' === $action || $request->request->has('title')) {
                    $promo
                        ->setCode($request->request->getString('code'))
                        ->setTag($request->request->getString('tag', $promo->getTag()))
                        ->setTitle($request->request->getString('title', $promo->getTitle()))
                        ->setBlurb($request->request->getString('blurb', $promo->getBlurb()))
                        ->setPercent(max(0, min(100, $request->request->getInt('percent', 0))))
                        ->setFreeDays(max(0, $request->request->getInt('free_days', 0)))
                        ->setMinDays(max(0, $request->request->getInt('min_days', 0)))
                        ->setMinAdvanceDays(max(0, $request->request->getInt('min_advance_days', 0)))
                        ->setStackable($request->request->getInt('stackable', 0))
                        ->setExpiresAt($request->request->getString('expires_at'))
                        ->setSortOrder($request->request->getInt('sort_order', 0));
                    $em->flush();
                    $this->addFlash('success', sprintf('Promo "%s" updated.', $promo->getTitle()));
                } elseif ($request->request->has('active')) {
                    $act = $request->request->getInt('active', 0);
                    $promo->setActive($act);
                    $em->flush();
                    $this->addFlash('success', sprintf('Promo "%s" switched %s.', $promo->getTitle(), $act ? 'on' : 'off'));
                }
            }

            return $this->redirectToRoute('admin_promo', ['view' => $viewRedirect]);
        }

        if ($dbReady) {
            $allEntities = $promoRepository->findBy([], ['sortOrder' => 'ASC', 'id' => 'ASC']);
            $allBookings = $bookingRepository->findAll();
            $all = array_map(static function (Promo $p) use ($allBookings): array {
                $uses = 0;
                if (null !== $p->getCode() && '' !== $p->getCode()) {
                    foreach ($allBookings as $b) {
                        if (null !== $b->getDiscountCode() && str_contains(strtoupper($b->getDiscountCode()), strtoupper($p->getCode()))) {
                            ++$uses;
                        }
                    }
                }

                return [
                    'id' => $p->getId(),
                    'code' => $p->getCode(),
                    'tag' => $p->getTag(),
                    'title' => $p->getTitle(),
                    'blurb' => $p->getBlurb(),
                    'percent' => $p->getPercent(),
                    'free_days' => $p->getFreeDays(),
                    'min_days' => $p->getMinDays(),
                    'min_advance_days' => $p->getMinAdvanceDays(),
                    'stackable' => $p->getStackable(),
                    'expires_at' => $p->getExpiresAt(),
                    'sort_order' => $p->getSortOrder(),
                    'active' => $p->getActive(),
                    'uses' => $uses,
                ];
            }, $allEntities);
            $unread = $messageRepository->count(['isRead' => 0]);
        } else {
            $all = $this->samplePromos();
            $unread = 2;
        }

        $stats = [
            'total' => \count($all),
            'live' => 0,
            'off' => 0,
            'gone' => 0,
        ];

        foreach ($all as $p) {
            $expired = null !== $p['expires_at'] && $p['expires_at'] < $today;
            if ($expired) {
                ++$stats['gone'];
            } elseif (1 === $p['active']) {
                ++$stats['live'];
            } else {
                ++$stats['off'];
            }
        }

        $promos = array_values(array_filter(
            $all,
            static function (array $p) use ($filter, $today): bool {
                $expired = null !== $p['expires_at'] && $p['expires_at'] < $today;
                $liveNow = 1 === $p['active'] && !$expired;

                return match ($filter) {
                    'live' => $liveNow,
                    'off' => 0 === $p['active'] && !$expired,
                    'expired' => $expired,
                    default => true,
                };
            }
        ));

        return $this->render('admin_promo/index.html.twig', [
            'stats' => $stats,
            'views' => self::VIEWS,
            'filter' => $filter,
            'promos' => $promos,
            'today' => $today,
            'unread' => $unread,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function samplePromos(): array
    {
        return [
            [
                'id' => 1,
                'code' => 'EARLY10',
                'tag' => 'Early Bird',
                'title' => 'Book 7 days ahead, save 10%',
                'blurb' => 'Plan your trip at least a week before pick-up and get 10% off the daily rate on any vehicle in the fleet.',
                'percent' => 10,
                'free_days' => 0,
                'min_days' => 2,
                'min_advance_days' => 7,
                'stackable' => 1,
                'expires_at' => null,
                'sort_order' => 1,
                'active' => 1,
                'uses' => 8,
            ],
        ];
    }
}
