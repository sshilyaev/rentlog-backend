<?php

namespace App\Property\Infrastructure\Persistence\Doctrine;

use App\Auth\Domain\Entity\User;
use App\Property\Domain\Entity\Property;
use App\Property\Domain\Entity\PropertyMember;
use App\Property\Domain\Enum\PropertyMemberRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class PropertyMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyMember::class);
    }

    public function findOneByPropertyAndUser(Property $property, User $user): ?PropertyMember
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.property = :property')
            ->andWhere('pm.user = :user')
            ->setParameter('property', $property)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function isLandlord(Property $property, User $user): bool
    {
        return $this->createQueryBuilder('pm')
            ->select('COUNT(pm.id)')
            ->andWhere('pm.property = :property')
            ->andWhere('pm.user = :user')
            ->andWhere('pm.role = :role')
            ->setParameter('property', $property)
            ->setParameter('user', $user)
            ->setParameter('role', PropertyMemberRole::Landlord)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function existsDuplicateForProperty(Property $property, ?User $user, ?string $email): bool
    {
        $qb = $this->createQueryBuilder('pm')
            ->select('COUNT(pm.id)')
            ->andWhere('pm.property = :property')
            ->setParameter('property', $property);

        if ($user !== null && $email !== null) {
            $qb->andWhere('pm.user = :user OR pm.email = :email')
                ->setParameter('user', $user)
                ->setParameter('email', mb_strtolower($email));

            return $qb->getQuery()->getSingleScalarResult() > 0;
        }

        if ($user !== null) {
            $qb->andWhere('pm.user = :user')
                ->setParameter('user', $user);

            return $qb->getQuery()->getSingleScalarResult() > 0;
        }

        if ($email !== null) {
            $qb->andWhere('pm.email = :email')
                ->setParameter('email', mb_strtolower($email));

            return $qb->getQuery()->getSingleScalarResult() > 0;
        }

        return false;
    }

    
    public function findUnlinkedByEmail(string $email): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.user IS NULL')
            ->andWhere('pm.email = :email')
            ->setParameter('email', mb_strtolower($email))
            ->orderBy('pm.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndProperty(string $memberId, Property $property): ?PropertyMember
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.id = :memberId')
            ->andWhere('pm.property = :property')
            ->setParameter('memberId', $memberId)
            ->setParameter('property', $property)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function findByProperty(Property $property): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.property = :property')
            ->setParameter('property', $property)
            ->orderBy('pm.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
