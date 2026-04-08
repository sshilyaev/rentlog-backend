<?php

namespace App\Rent\Application\Service;

use App\Property\Domain\Entity\Property;
use App\Property\Domain\Entity\PropertyMember;
use App\Rent\Application\Dto\UpsertRentTermsRequestDto;
use App\Rent\Domain\Entity\RentTerms;
use App\Rent\Domain\Enum\RentTermsStatus;
use App\Rent\Infrastructure\Persistence\Doctrine\RentTermsRepository;
use Doctrine\ORM\EntityManagerInterface;

final class UpsertRentTermsService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RentTermsRepository $rentTermsRepository
    ) {
    }

    public function handle(Property $property, ?PropertyMember $member, UpsertRentTermsRequestDto $dto): RentTerms
    {
        if ($member === null) {
            return $this->upsertBase($property, $dto);
        }

        return $this->upsertMemberTerms($property, $member, $dto);
    }

    private function upsertBase(Property $property, UpsertRentTermsRequestDto $dto): RentTerms
    {
        if (
            $dto->baseRentAmount === null
            || $dto->currency === null
            || $dto->billingDay === null
            || $dto->startsAt === null
        ) {
            throw new \InvalidArgumentException('Для общих условий аренды обязательны сумма, валюта, число оплаты и дата начала.');
        }

        $existing = $this->rentTermsRepository->findBaseByProperty($property);
        $status = RentTermsStatus::from($dto->status ?? RentTermsStatus::Active->value);
        $startsAt = new \DateTimeImmutable($dto->startsAt);
        $endsAt = $dto->endsAt !== null ? new \DateTimeImmutable($dto->endsAt) : null;

        if ($existing instanceof RentTerms) {
            $existing->update(
                baseRentAmount: $dto->baseRentAmount,
                currency: $dto->currency,
                billingDay: $dto->billingDay,
                startsAt: $startsAt,
                endsAt: $endsAt,
                notes: $dto->notes,
                status: $status
            );

            $this->entityManager->flush();

            return $existing;
        }

        $rentTerms = new RentTerms(
            property: $property,
            propertyMember: null,
            baseRentAmount: $dto->baseRentAmount,
            currency: $dto->currency,
            billingDay: $dto->billingDay,
            startsAt: $startsAt,
            endsAt: $endsAt,
            notes: $dto->notes,
            status: $status
        );

        $this->entityManager->persist($rentTerms);
        $this->entityManager->flush();

        return $rentTerms;
    }

    private function upsertMemberTerms(Property $property, PropertyMember $member, UpsertRentTermsRequestDto $dto): RentTerms
    {
        $baseTerms = $this->rentTermsRepository->findBaseByProperty($property);

        if (!$baseTerms instanceof RentTerms) {
            throw new \InvalidArgumentException('Сначала нужно задать общие условия аренды для объекта.');
        }

        $existing = $this->rentTermsRepository->findMemberTerms($property, $member);
        $current = $existing ?? $baseTerms;

        $rentTerms = $existing ?? new RentTerms(
            property: $property,
            propertyMember: $member,
            baseRentAmount: $current->getBaseRentAmount(),
            currency: $current->getCurrency(),
            billingDay: $current->getBillingDay(),
            startsAt: $current->getStartsAt(),
            endsAt: $current->getEndsAt(),
            notes: $current->getNotes(),
            status: $current->getStatus()
        );

        $rentTerms->update(
            baseRentAmount: $dto->baseRentAmount ?? $current->getBaseRentAmount(),
            currency: $dto->currency ?? $current->getCurrency(),
            billingDay: $dto->billingDay ?? $current->getBillingDay(),
            startsAt: $dto->startsAt !== null ? new \DateTimeImmutable($dto->startsAt) : $current->getStartsAt(),
            endsAt: $dto->endsAt !== null ? new \DateTimeImmutable($dto->endsAt) : $current->getEndsAt(),
            notes: $dto->notes ?? $current->getNotes(),
            status: RentTermsStatus::from($dto->status ?? $current->getStatus()->value)
        );

        if (!$existing instanceof RentTerms) {
            $this->entityManager->persist($rentTerms);
        }

        $this->entityManager->flush();

        return $rentTerms;
    }
}
