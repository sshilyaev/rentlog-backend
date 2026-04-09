<?php

namespace App\Billing\Application\Service;

use App\Billing\Application\Dto\CreateMeterRequestDto;
use App\Billing\Domain\Entity\Meter;
use App\Billing\Domain\Enum\MeterUnit;
use App\Billing\Infrastructure\Persistence\Doctrine\MeterRepository;
use App\Property\Domain\Entity\Property;
use Doctrine\ORM\EntityManagerInterface;

final class CreateMeterService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MeterRepository $meterRepository
    ) {
    }

    public function handle(Property $property, CreateMeterRequestDto $dto): Meter
    {
        if ($this->meterRepository->existsByCode($property, $dto->code)) {
            throw new \InvalidArgumentException('Счетчик с таким кодом уже существует для объекта.');
        }

        $unit = MeterUnit::tryFrom($dto->unit) ?? MeterUnit::tryFromLoose($dto->unit);
        if ($unit === null) {
            throw new \InvalidArgumentException('Неизвестная единица измерения.');
        }

        $meter = new Meter(
            property: $property,
            code: $dto->code,
            title: $dto->title,
            unit: $unit
        );

        $this->entityManager->persist($meter);
        $this->entityManager->flush();

        return $meter;
    }
}
