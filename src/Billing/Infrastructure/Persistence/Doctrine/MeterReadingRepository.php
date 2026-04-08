<?php

namespace App\Billing\Infrastructure\Persistence\Doctrine;

use App\Billing\Domain\Entity\Meter;
use App\Billing\Domain\Entity\MeterReading;
use App\Billing\Domain\Enum\MeterReadingType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MeterReadingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MeterReading::class);
    }

    /**
     * @return list<MeterReading>
     */
    public function findByMeter(Meter $meter): array
    {
        return $this->createQueryBuilder('reading')
            ->andWhere('reading.meter = :meter')
            ->setParameter('meter', $meter)
            ->orderBy('reading.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function hasInitialReading(Meter $meter): bool
    {
        return $this->createQueryBuilder('reading')
            ->select('COUNT(reading.id)')
            ->andWhere('reading.meter = :meter')
            ->andWhere('reading.type = :type')
            ->setParameter('meter', $meter)
            ->setParameter('type', MeterReadingType::Initial)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function existsMonthlyReading(Meter $meter, int $billingYear, int $billingMonth): bool
    {
        return $this->createQueryBuilder('reading')
            ->select('COUNT(reading.id)')
            ->andWhere('reading.meter = :meter')
            ->andWhere('reading.type = :type')
            ->andWhere('reading.billingYear = :billingYear')
            ->andWhere('reading.billingMonth = :billingMonth')
            ->setParameter('meter', $meter)
            ->setParameter('type', MeterReadingType::Monthly)
            ->setParameter('billingYear', $billingYear)
            ->setParameter('billingMonth', $billingMonth)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
