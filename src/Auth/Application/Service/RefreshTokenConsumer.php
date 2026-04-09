<?php

declare(strict_types=1);

namespace App\Auth\Application\Service;

use App\Auth\Domain\Entity\User;
use App\Auth\Infrastructure\Persistence\Doctrine\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

final class RefreshTokenConsumer
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RefreshTokenRepository $refreshTokenRepository,
    ) {
    }

    /** Validates one-time refresh token, removes row, returns user. */
    public function consume(string $plainRefreshToken): ?User
    {
        $hash = hash('sha256', $plainRefreshToken);
        $row = $this->refreshTokenRepository->findValidByTokenHash($hash);
        if ($row === null) {
            return null;
        }

        $user = $row->getUser();
        $this->entityManager->remove($row);
        $this->entityManager->flush();

        return $user;
    }
}
