<?php

namespace App\Billing\Infrastructure\Persistence\Doctrine;

use App\Billing\Domain\Entity\Meter;
use App\Property\Domain\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MeterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Meter::class);
    }

    
    public function findByProperty(Property $property): array
    {
        return $this->createQueryBuilder('meter')
            ->andWhere('meter.property = :property')
            ->setParameter('property', $property)
            ->orderBy('meter.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndProperty(string $meterId, Property $property): ?Meter
    {
        return $this->createQueryBuilder('meter')
            ->andWhere('meter.id = :meterId')
            ->andWhere('meter.property = :property')
            ->setParameter('meterId', $meterId)
            ->setParameter('property', $property)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsByCode(Property $property, string $code): bool
    {
        return $this->createQueryBuilder('meter')
            ->select('COUNT(meter.id)')
            ->andWhere('meter.property = :property')
            ->andWhere('meter.code = :code')
            ->setParameter('property', $property)
            ->setParameter('code', mb_strtolower($code))
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
