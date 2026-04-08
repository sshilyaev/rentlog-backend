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
        return $this->createQueryBuilder('member')
            ->andWhere('member.property = :property')
            ->andWhere('member.user = :user')
            ->setParameter('property', $property)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function isLandlord(Property $property, User $user): bool
    {
        return $this->createQueryBuilder('member')
            ->select('COUNT(member.id)')
            ->andWhere('member.property = :property')
            ->andWhere('member.user = :user')
            ->andWhere('member.role = :role')
            ->setParameter('property', $property)
            ->setParameter('user', $user)
            ->setParameter('role', PropertyMemberRole::Landlord)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function existsDuplicateForProperty(Property $property, ?User $user, ?string $email): bool
    {
        $qb = $this->createQueryBuilder('member')
            ->select('COUNT(member.id)')
            ->andWhere('member.property = :property')
            ->setParameter('property', $property);

        if ($user !== null && $email !== null) {
            $qb->andWhere('member.user = :user OR member.email = :email')
                ->setParameter('user', $user)
                ->setParameter('email', mb_strtolower($email));

            return $qb->getQuery()->getSingleScalarResult() > 0;
        }

        if ($user !== null) {
            $qb->andWhere('member.user = :user')
                ->setParameter('user', $user);

            return $qb->getQuery()->getSingleScalarResult() > 0;
        }

        if ($email !== null) {
            $qb->andWhere('member.email = :email')
                ->setParameter('email', mb_strtolower($email));

            return $qb->getQuery()->getSingleScalarResult() > 0;
        }

        return false;
    }

    /**
     * @return list<PropertyMember>
     */
    public function findUnlinkedByEmail(string $email): array
    {
        return $this->createQueryBuilder('member')
            ->andWhere('member.user IS NULL')
            ->andWhere('member.email = :email')
            ->setParameter('email', mb_strtolower($email))
            ->orderBy('member.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndProperty(string $memberId, Property $property): ?PropertyMember
    {
        return $this->createQueryBuilder('member')
            ->andWhere('member.id = :memberId')
            ->andWhere('member.property = :property')
            ->setParameter('memberId', $memberId)
            ->setParameter('property', $property)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<PropertyMember>
     */
    public function findByProperty(Property $property): array
    {
        return $this->createQueryBuilder('member')
            ->andWhere('member.property = :property')
            ->setParameter('property', $property)
            ->orderBy('member.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
