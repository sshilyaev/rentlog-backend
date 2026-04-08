<?php

namespace App\Billing\Application\Service;

use App\Billing\Application\Dto\CreateMeterRequestDto;
use App\Billing\Domain\Entity\Meter;
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

        $meter = new Meter(
            property: $property,
            code: $dto->code,
            title: $dto->title,
            unit: $dto->unit
        );

        $this->entityManager->persist($meter);
        $this->entityManager->flush();

        return $meter;
    }
}
