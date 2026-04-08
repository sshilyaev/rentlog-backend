<?php

namespace App\Property\Application\Service;

use App\Auth\Domain\Entity\User;
use App\Property\Application\Dto\CreatePropertyRequestDto;
use App\Property\Domain\Entity\Property;
use App\Property\Domain\Entity\PropertyMember;
use App\Property\Domain\Enum\PropertyMemberRole;
use App\Property\Domain\Enum\PropertyMemberStatus;
use App\Property\Domain\Enum\PropertyType;
use Doctrine\ORM\EntityManagerInterface;

final class CreatePropertyService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function handle(User $user, CreatePropertyRequestDto $dto): Property
    {
        $property = new Property(
            title: $dto->title,
            typeCode: PropertyType::from($dto->typeCode),
            address: $dto->address,
            description: $dto->description,
            metadata: $dto->metadata
        );

        $landlordMember = new PropertyMember(
            property: $property,
            user: $user,
            role: PropertyMemberRole::Landlord,
            status: PropertyMemberStatus::Active,
            fullName: $user->getFullName(),
            email: $user->getEmail()
        );

        $this->entityManager->persist($property);
        $this->entityManager->persist($landlordMember);
        $this->entityManager->flush();

        return $property;
    }
}
