<?php

declare(strict_types=1);

namespace App\Auth\Application\Service;

use App\Auth\Domain\Entity\User;
use App\Auth\Infrastructure\Persistence\Doctrine\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordResetService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly AuthEmailService $authEmailService,
    ) {
    }

    /** Always succeeds from caller's perspective (no user enumeration). */
    public function requestReset(string $email): void
    {
        $user = $this->userRepository->findOneByEmail($email);
        if ($user === null) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $user->setPasswordResetRequest($token, new \DateTimeImmutable('+1 hour'));
        $this->entityManager->flush();

        $this->authEmailService->sendPasswordReset($user, $token);
    }

    public function resetPassword(string $token, string $plainPassword): ?User
    {
        $user = $this->userRepository->findOneByPasswordResetToken($token);
        if ($user === null) {
            return null;
        }

        $expires = $user->getPasswordResetExpiresAt();
        if ($expires === null || $expires < new \DateTimeImmutable()) {
            return null;
        }

        $user->updatePassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->clearPasswordResetRequest();
        $this->entityManager->flush();

        return $user;
    }
}
