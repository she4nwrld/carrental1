<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * @return Booking[]
     */
    public function findByStatusFilter(string $filter, string $today): array
    {
        $qb = $this->createQueryBuilder('b')
            ->orderBy('b.id', 'DESC');

        if ('overdue' === $filter) {
            $qb->andWhere('b.status = :confirmed AND b.returnDate < :today')
               ->setParameter('confirmed', 'confirmed')
               ->setParameter('today', $today);
        } elseif ('all' !== $filter) {
            $qb->andWhere('b.status = :status')
               ->setParameter('status', $filter);
        }

        return $qb->getQuery()->getResult();
    }
}
