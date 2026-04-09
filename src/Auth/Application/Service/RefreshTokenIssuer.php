<?php

declare(strict_types=1);

namespace App\Auth\Application\Service;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\Entity\User;
use App\Auth\Infrastructure\Persistence\Doctrine\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

final class RefreshTokenIssuer
{
    public const TTL_SECONDS = 30 * 24 * 3600;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RefreshTokenRepository $refreshTokenRepository,
    ) {
    }

    /** @return string Plain refresh token (only time it is visible) */
    public function issue(User $user): string
    {
        $plain = bin2hex(random_bytes(32));
        $hash = hash('sha256', $plain);
        $expiresAt = (new \DateTimeImmutable())->modify('+'.self::TTL_SECONDS.' seconds');
        $this->entityManager->persist(new RefreshToken($user, $hash, $expiresAt));
        $this->entityManager->flush();

        return $plain;
    }

    public function revokeAllForUser(User $user): void
    {
        $this->refreshTokenRepository->removeAllForUser($user);
        $this->entityManager->flush();
    }

    public function hashPlainToken(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
