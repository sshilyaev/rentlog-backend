<?php

namespace App\Auth\Infrastructure\Persistence\Doctrine;

use App\Auth\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
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

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => mb_strtolower($email)]);
    }

    public function existsAnyAdmin(): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        if (!$conn->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            foreach ($this->findAll() as $user) {
                if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                    return true;
                }
            }

            return false;
        }

        $sql = 'SELECT EXISTS(SELECT 1 FROM users WHERE roles::jsonb @> :json::jsonb)';

        return (bool) $conn->fetchOne($sql, ['json' => json_encode(['ROLE_ADMIN'])]);
    }
}
