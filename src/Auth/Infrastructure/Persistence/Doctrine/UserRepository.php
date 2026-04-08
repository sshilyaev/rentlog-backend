<?php

namespace App\Auth\Infrastructure\Persistence\Doctrine;

use App\Auth\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function existsByEmail(string $email): bool
    {
        return $this->createQueryBuilder('user')
            ->select('COUNT(user.email)')
            ->andWhere('user.email = :email')
            ->setParameter('email', mb_strtolower($email))
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
