<?php

namespace App\Billing\Infrastructure\Persistence\Doctrine;

use App\Billing\Domain\Entity\BillingParameter;
use App\Property\Domain\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class BillingParameterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BillingParameter::class);
    }

    /**
     * @return list<BillingParameter>
     */
    public function findByProperty(Property $property): array
    {
        return $this->createQueryBuilder('parameter')
            ->leftJoin('parameter.meter', 'meter')
            ->addSelect('meter')
            ->andWhere('parameter.property = :property')
            ->setParameter('property', $property)
            ->orderBy('parameter.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndProperty(string $parameterId, Property $property): ?BillingParameter
    {
        return $this->createQueryBuilder('parameter')
            ->leftJoin('parameter.meter', 'meter')
            ->addSelect('meter')
            ->andWhere('parameter.id = :parameterId')
            ->andWhere('parameter.property = :property')
            ->setParameter('parameterId', $parameterId)
            ->setParameter('property', $property)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsByCode(Property $property, string $code): bool
    {
        return $this->createQueryBuilder('parameter')
            ->select('COUNT(parameter.id)')
            ->andWhere('parameter.property = :property')
            ->andWhere('parameter.code = :code')
            ->setParameter('property', $property)
            ->setParameter('code', mb_strtolower($code))
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
