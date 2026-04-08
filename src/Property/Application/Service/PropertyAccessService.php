<?php

namespace App\Property\Application\Service;

use App\Auth\Domain\Entity\User;
use App\Property\Domain\Entity\Property;
use App\Property\Domain\Entity\PropertyMember;
use App\Property\Domain\Exception\PropertyAccessDeniedException;
use App\Property\Domain\Exception\PropertyNotFoundException;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyMemberRepository;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyRepository;

final class PropertyAccessService
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly PropertyMemberRepository $propertyMemberRepository
    ) {
    }

    public function getAccessibleProperty(string $propertyId, User $user): Property
    {
        $property = $this->propertyRepository->find($propertyId);

        if (!$property instanceof Property) {
            throw new PropertyNotFoundException();
        }

        $member = $this->propertyMemberRepository->findOneByPropertyAndUser($property, $user);

        if (!$member instanceof PropertyMember) {
            throw new PropertyAccessDeniedException();
        }

        return $property;
    }

    public function assertLandlord(Property $property, User $user): void
    {
        if (!$this->propertyMemberRepository->isLandlord($property, $user)) {
            throw new PropertyAccessDeniedException();
        }
    }
}
