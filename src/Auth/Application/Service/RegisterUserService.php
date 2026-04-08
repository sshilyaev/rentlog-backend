<?php

namespace App\Auth\Application\Service;

use App\Auth\Application\Dto\RegisterRequestDto;
use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Exception\UserAlreadyExistsException;
use App\Auth\Infrastructure\Persistence\Doctrine\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function handle(RegisterRequestDto $dto): User
    {
        if ($this->userRepository->existsByEmail($dto->email)) {
            throw new UserAlreadyExistsException($dto->email);
        }

        $user = new User(
            email: $dto->email,
            password: '',
            fullName: $dto->fullName
        );

        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->updatePassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
