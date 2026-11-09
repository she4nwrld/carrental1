<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\CarRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminMessageController extends AbstractController
{
    private const VIEWS = ['all', 'unread', 'read'];

    #[Route('/admin/messages', name: 'admin_message', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        MessageRepository $messageRepository,
        CarRepository $carRepository,
        EntityManagerInterface $em,
    ): Response {
        $filter = (string) $request->query->get('view', 'all');
        if (!\in_array($filter, self::VIEWS, true)) {
            $filter = 'all';
        }

        $dbReady = $carRepository->ensureSeeded();

        if ($dbReady && $request->isMethod('POST')) {
            $action = (string) $request->request->get('action', '');
            $msgId = (int) $request->request->get('message_id', 0);
            $viewRedirect = (string) $request->request->get('view', $filter);
            $msg = $msgId > 0 ? $messageRepository->find($msgId) : null;

            if (null !== $msg) {
                if ('delete' === $action) {
                    $em->remove($msg);
                    $em->flush();
                    $this->addFlash('success', 'Message deleted.');
                } elseif ($request->request->has('is_read')) {
                    $read = $request->request->getInt('is_read', 1);
                    $msg->setIsRead($read);
                    $em->flush();
                    $this->addFlash('success', sprintf('Message from %s marked as %s.', $msg->getName(), $read ? 'handled' : 'unread'));
                }
            }

            return $this->redirectToRoute('admin_message', ['view' => $viewRedirect]);
        }

        if ($dbReady) {
            $allEntities = $messageRepository->findBy([], ['id' => 'DESC']);
            $all = array_map(static fn (Message $m) => [
                'id' => $m->getId(),
                'name' => $m->getName(),
                'email' => $m->getEmail(),
                'phone' => $m->getPhone(),
                'account_name' => $m->getAccountName(),
                'body' => $m->getBody(),
                'is_read' => $m->getIsRead(),
                'created_at' => $m->getCreatedAt(),
            ], $allEntities);
        } else {
            $all = $this->sampleMessages();
        }

        $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $stats = [
            'unread' => 0,
            'total' => \count($all),
            'week' => 0,
            'members' => 0,
        ];

        foreach ($all as $m) {
            if (0 === $m['is_read']) {
                ++$stats['unread'];
            }
            if ($m['created_at'] >= $sevenDaysAgo) {
                ++$stats['week'];
            }
            if (null !== $m['account_name']) {
                ++$stats['members'];
            }
        }

        $messages = array_values(array_filter(
            $all,
            static fn (array $m): bool => match ($filter) {
                'unread' => 0 === $m['is_read'],
                'read' => 1 === $m['is_read'],
                default => true,
            }
        ));

        return $this->render('admin_message/index.html.twig', [
            'stats' => $stats,
            'views' => self::VIEWS,
            'filter' => $filter,
            'messages' => $messages,
            'unread' => $stats['unread'],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sampleMessages(): array
    {
        return [
            [
                'id' => 7,
                'name' => 'Roberto Villanueva',
                'email' => 'roberto.v@company.ph',
                'phone' => '0917 990 1122',
                'account_name' => 'Marco Santos',
                'body' => 'Good day! We need two Toyota Hiace vans for a corporate team building on November 20–22. Do you issue BIR official receipts?',
                'is_read' => 0,
                'created_at' => '2026-11-06 16:40:00',
            ],
        ];
    }
}
