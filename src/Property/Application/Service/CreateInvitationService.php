<?php

namespace App\Property\Application\Service;

use App\Auth\Domain\Entity\User;
use App\Property\Application\Dto\CreateInvitationRequestDto;
use App\Property\Domain\Entity\Invitation;
use App\Property\Domain\Entity\Property;
use App\Property\Domain\Entity\PropertyMember;
use App\Property\Infrastructure\Persistence\Doctrine\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CreateInvitationService
{
    public function __construct(
        private readonly InvitationRepository $invitationRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function handle(
        Property $property,
        PropertyMember $member,
        User $createdBy,
        CreateInvitationRequestDto $dto
    ): Invitation {
        $existing = $this->invitationRepository->findActiveForMember($member);

        if ($existing instanceof Invitation) {
            return $existing;
        }

        $invitation = new Invitation(
            property: $property,
            propertyMember: $member,
            createdBy: $createdBy,
            targetEmail: $member->getEmail(),
            expiresAt: new \DateTimeImmutable(sprintf('+%d hours', $dto->expiresInHours))
        );

        $this->entityManager->persist($invitation);
        $this->entityManager->flush();

        return $invitation;
    }
}
