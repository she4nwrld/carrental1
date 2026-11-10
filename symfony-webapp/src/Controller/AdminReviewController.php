<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\CarRepository;
use App\Repository\MessageRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STAFF')]
final class AdminReviewController extends AbstractController
{
    private const VIEWS = ['all', 'live', 'hidden'];

    #[Route('/admin/reviews', name: 'admin_review', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ReviewRepository $reviewRepository,
        CarRepository $carRepository,
        MessageRepository $messageRepository,
        EntityManagerInterface $em,
    ): Response {
        $filter = (string) $request->query->get('view', 'all');
        if (!\in_array($filter, self::VIEWS, true)) {
            $filter = 'all';
        }

        $dbReady = $carRepository->ensureSeeded();

        if ($dbReady && $request->isMethod('POST')) {
            $action = (string) $request->request->get('action', '');
            $reviewId = (int) $request->request->get('review_id', 0);
            $viewRedirect = (string) $request->request->get('view', $filter);
            $rv = $reviewId > 0 ? $reviewRepository->find($reviewId) : null;

            if (null !== $rv) {
                if ('delete' === $action) {
                    $em->remove($rv);
                    $em->flush();
                    $this->addFlash('success', 'Review deleted.');
                } elseif ($request->request->has('approved')) {
                    $appr = '1' === (string) $request->request->get('approved', '0');
                    $rv->setApproved($appr);
                    $em->flush();
                    $this->addFlash('success', sprintf('Review by %s is now %s.', $rv->getFullName(), $appr ? 'live' : 'hidden'));
                }
            }

            return $this->redirectToRoute('admin_review', ['view' => $viewRedirect]);
        }

        if ($dbReady) {
            $allEntities = $reviewRepository->findBy([], ['id' => 'DESC']);
            $all = array_map(static fn (Review $r) => [
                'id' => $r->getId(),
                'full_name' => $r->getFullName(),
                'email' => $r->getEmail(),
                'rating' => $r->getRating(),
                'review_text' => $r->getReviewText(),
                'approved' => $r->isApproved(),
                'created_at' => $r->getCreatedAt(),
            ], $allEntities);
            $unread = $messageRepository->count(['isRead' => 0]);
        } else {
            $all = $this->sampleReviews();
            $unread = 2;
        }

        $shown = 0;
        $ratingSum = 0;

        foreach ($all as $rv) {
            if ($rv['approved']) {
                ++$shown;
                $ratingSum += $rv['rating'];
            }
        }

        $stats = [
            'total' => \count($all),
            'shown' => $shown,
            'hidden' => \count($all) - $shown,
            'avg_rating' => $shown > 0 ? number_format($ratingSum / $shown, 1) : null,
        ];

        $reviews = array_values(array_filter(
            $all,
            static fn (array $rv): bool => match ($filter) {
                'live' => true === $rv['approved'],
                'hidden' => false === $rv['approved'],
                default => true,
            }
        ));

        return $this->render('admin_review/index.html.twig', [
            'stats' => $stats,
            'views' => self::VIEWS,
            'filter' => $filter,
            'reviews' => $reviews,
            'unread' => $unread,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sampleReviews(): array
    {
        return [
            [
                'id' => 9,
                'full_name' => 'Marco Santos',
                'email' => 'marco.santos@gmail.com',
                'rating' => 5,
                'review_text' => 'Picked up the Innova at Mactan Airport right after landing. Ice-cold aircon, super clean interior, and return was under 5 minutes.',
                'approved' => true,
                'created_at' => '2026-10-29 18:12:00',
            ],
        ];
    }
}
