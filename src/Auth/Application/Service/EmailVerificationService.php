<?php

declare(strict_types=1);

namespace App\Auth\Application\Service;

use App\Auth\Domain\Entity\User;
use App\Auth\Infrastructure\Persistence\Doctrine\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class EmailVerificationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function verifyByToken(string $token): ?User
    {
        $user = $this->userRepository->findOneByEmailVerificationToken($token);
        if ($user === null) {
            return null;
        }

        $user->verifyEmail();
        $this->entityManager->flush();

        return $user;
    }
}
