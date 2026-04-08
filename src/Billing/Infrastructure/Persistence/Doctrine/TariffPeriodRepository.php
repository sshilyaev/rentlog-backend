<?php

namespace App\Billing\Infrastructure\Persistence\Doctrine;

use App\Billing\Domain\Entity\BillingParameter;
use App\Billing\Domain\Entity\TariffPeriod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class TariffPeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TariffPeriod::class);
    }

    /**
     * @return list<TariffPeriod>
     */
    public function findByParameter(BillingParameter $billingParameter): array
    {
        return $this->createQueryBuilder('tariff')
            ->andWhere('tariff.billingParameter = :billingParameter')
            ->setParameter('billingParameter', $billingParameter)
            ->orderBy('tariff.effectiveFrom', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function hasOverlap(BillingParameter $billingParameter, \DateTimeImmutable $effectiveFrom, ?\DateTimeImmutable $effectiveTo): bool
    {
        $qb = $this->createQueryBuilder('tariff')
            ->select('COUNT(tariff.id)')
            ->andWhere('tariff.billingParameter = :billingParameter')
            ->andWhere('tariff.effectiveFrom <= :newEnd OR :newEnd IS NULL')
            ->andWhere('tariff.effectiveTo IS NULL OR tariff.effectiveTo >= :newStart')
            ->setParameter('billingParameter', $billingParameter)
            ->setParameter('newStart', $effectiveFrom)
            ->setParameter('newEnd', $effectiveTo);

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
