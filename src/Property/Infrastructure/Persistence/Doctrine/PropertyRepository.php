<?php

namespace App\Property\Infrastructure\Persistence\Doctrine;

use App\Auth\Domain\Entity\User;
use App\Property\Domain\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    /**
     * @return list<Property>
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('property')
            ->innerJoin('property.members', 'member')
            ->andWhere('member.user = :user')
            ->setParameter('user', $user)
            ->orderBy('property.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
