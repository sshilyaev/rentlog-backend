<?php

namespace App\Property\Application\Service;

use App\Auth\Domain\Entity\User;
use App\Property\Domain\Entity\Invitation;
use App\Property\Domain\Exception\InvitationAlreadyClaimedException;
use App\Property\Domain\Exception\InvitationEmailMismatchException;
use App\Property\Domain\Exception\InvitationExpiredException;
use App\Property\Domain\Exception\InvitationMemberAlreadyLinkedException;
use App\Property\Domain\Exception\InvitationNotFoundException;
use App\Property\Infrastructure\Persistence\Doctrine\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ClaimInvitationService
{
    public function __construct(
        private readonly InvitationRepository $invitationRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function handle(string $code, User $user): Invitation
    {
        $invitation = $this->invitationRepository->findOneByCode($code);

        if (!$invitation instanceof Invitation) {
            throw new InvitationNotFoundException();
        }

        if ($invitation->isClaimed()) {
            throw new InvitationAlreadyClaimedException();
        }

        if ($invitation->isExpired()) {
            throw new InvitationExpiredException();
        }

        $targetEmail = $invitation->getTargetEmail();

        if ($targetEmail !== null && $targetEmail !== mb_strtolower($user->getEmail())) {
            throw new InvitationEmailMismatchException();
        }

        $member = $invitation->getPropertyMember();

        if ($member->getUser() !== null && $member->getUser()->getId() !== $user->getId()) {
            throw new InvitationMemberAlreadyLinkedException();
        }

        if ($member->getUser() === null) {
            $member->linkUser($user);
        }

        $invitation->markClaimed();

        $this->entityManager->flush();

        return $invitation;
    }
}
