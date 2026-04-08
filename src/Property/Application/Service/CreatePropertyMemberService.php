<?php

namespace App\Property\Application\Service;

use App\Auth\Infrastructure\Persistence\Doctrine\UserRepository;
use App\Property\Application\Dto\CreatePropertyMemberRequestDto;
use App\Property\Domain\Entity\Property;
use App\Property\Domain\Entity\PropertyMember;
use App\Property\Domain\Exception\PropertyMemberAlreadyExistsException;
use App\Property\Domain\Enum\PropertyMemberRole;
use App\Property\Domain\Enum\PropertyMemberStatus;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyMemberRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CreatePropertyMemberService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PropertyMemberRepository $propertyMemberRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function handle(Property $property, CreatePropertyMemberRequestDto $dto): PropertyMember
    {
        $user = null;
        $email = $dto->email !== null ? mb_strtolower($dto->email) : null;

        if ($email !== null) {
            $user = $this->userRepository->findOneBy(['email' => $email]);
        }

        if ($this->propertyMemberRepository->existsDuplicateForProperty($property, $user, $email)) {
            throw new PropertyMemberAlreadyExistsException();
        }

        $status = match (true) {
            $user !== null => PropertyMemberStatus::Active,
            $email !== null => PropertyMemberStatus::Invited,
            default => PropertyMemberStatus::Placeholder,
        };

        $member = new PropertyMember(
            property: $property,
            user: $user,
            role: PropertyMemberRole::Tenant,
            status: $status,
            fullName: $dto->fullName,
            email: $email,
            phone: $dto->phone
        );

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }
}
