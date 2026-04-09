<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Bootstrap;

use App\Auth\Domain\Entity\User;
use App\Auth\Infrastructure\Persistence\Doctrine\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class EnsureDefaultAdminSubscriber implements EventSubscriberInterface
{
    private static bool $ran = false;

    public function __construct(
        private readonly string $kernelEnvironment,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly string $defaultAdminEmail,
        private readonly string $defaultAdminPassword,
        private readonly string $defaultAdminFullName,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 96]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->kernelEnvironment !== 'prod') {
            return;
        }

        if (self::$ran) {
            return;
        }

        if ($this->defaultAdminEmail === '' || $this->defaultAdminPassword === '') {
            return;
        }

        if ($this->userRepository->existsAnyAdmin()) {
            self::$ran = true;

            return;
        }

        $user = new User($this->defaultAdminEmail, '', $this->defaultAdminFullName);
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $user->updatePassword($this->passwordHasher->hashPassword($user, $this->defaultAdminPassword));

        $this->entityManager->persist($user);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
        }

        self::$ran = true;
    }
}
