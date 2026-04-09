<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Persistence\Doctrine;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findValidByTokenHash(string $tokenHash): ?RefreshToken
    {
        $row = $this->createQueryBuilder('r')
            ->andWhere('r.tokenHash = :h')
            ->andWhere('r.expiresAt > :now')
            ->setParameter('h', $tokenHash)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $row instanceof RefreshToken ? $row : null;
    }

    public function removeAllForUser(User $user): void
    {
        $this->createQueryBuilder('r')
            ->delete()
            ->andWhere('r.user = :u')
            ->setParameter('u', $user)
            ->getQuery()
            ->execute();
    }
}
