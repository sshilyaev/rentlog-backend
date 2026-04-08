<?php

namespace App\Rent\Infrastructure\Persistence\Doctrine;

use App\Property\Domain\Entity\Property;
use App\Property\Domain\Entity\PropertyMember;
use App\Rent\Domain\Entity\RentTerms;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RentTermsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RentTerms::class);
    }

    public function findBaseByProperty(Property $property): ?RentTerms
    {
        return $this->createQueryBuilder('rentTerms')
            ->andWhere('rentTerms.property = :property')
            ->andWhere('rentTerms.propertyMember IS NULL')
            ->setParameter('property', $property)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findMemberTerms(Property $property, PropertyMember $member): ?RentTerms
    {
        return $this->createQueryBuilder('rentTerms')
            ->andWhere('rentTerms.property = :property')
            ->andWhere('rentTerms.propertyMember = :member')
            ->setParameter('property', $property)
            ->setParameter('member', $member)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
