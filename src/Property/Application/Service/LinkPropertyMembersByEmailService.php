<?php

namespace App\Property\Application\Service;

use App\Auth\Domain\Entity\User;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyMemberRepository;
use Doctrine\ORM\EntityManagerInterface;

final class LinkPropertyMembersByEmailService
{
    public function __construct(
        private readonly PropertyMemberRepository $propertyMemberRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function handle(User $user): int
    {
        $members = $this->propertyMemberRepository->findUnlinkedByEmail($user->getEmail());

        $linkedCount = 0;

        foreach ($members as $member) {
            $member->linkUser($user);
            ++$linkedCount;
        }

        if ($linkedCount > 0) {
            $this->entityManager->flush();
        }

        return $linkedCount;
    }
}
