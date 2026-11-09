<?php

namespace App\Repository;

use App\Entity\Promo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promo>
 */
class PromoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promo::class);
    }

    /**
     * @return Promo[]
     */
    public function findLivePromos(string $today): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.active = 1')
            ->andWhere('p.expiresAt IS NULL OR p.expiresAt >= :today')
            ->setParameter('today', $today)
            ->orderBy('p.sortOrder', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
