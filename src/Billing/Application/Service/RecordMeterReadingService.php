<?php

namespace App\Billing\Application\Service;

use App\Auth\Domain\Entity\User;
use App\Billing\Application\Dto\CreateMeterReadingRequestDto;
use App\Billing\Domain\Entity\Meter;
use App\Billing\Domain\Entity\MeterReading;
use App\Billing\Domain\Enum\MeterReadingType;
use App\Billing\Infrastructure\Persistence\Doctrine\MeterReadingRepository;
use Doctrine\ORM\EntityManagerInterface;

final class RecordMeterReadingService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MeterReadingRepository $meterReadingRepository
    ) {
    }

    public function createInitial(Meter $meter, User $user, CreateMeterReadingRequestDto $dto): MeterReading
    {
        if ($this->meterReadingRepository->hasInitialReading($meter)) {
            throw new \InvalidArgumentException('Стартовые показатели для этого счетчика уже заданы.');
        }

        $reading = new MeterReading(
            meter: $meter,
            recordedByUser: $user,
            type: MeterReadingType::Initial,
            value: $dto->value,
            comment: $dto->comment
        );

        $this->entityManager->persist($reading);
        $this->entityManager->flush();

        return $reading;
    }

    public function createMonthly(Meter $meter, User $user, CreateMeterReadingRequestDto $dto): MeterReading
    {
        if (!$this->meterReadingRepository->hasInitialReading($meter)) {
            throw new \InvalidArgumentException('Сначала нужно внести стартовые показатели счетчика.');
        }

        if ($dto->billingYear === null || $dto->billingMonth === null) {
            throw new \InvalidArgumentException('Для ежемесячного показания нужно указать год и месяц периода.');
        }

        if ($this->meterReadingRepository->existsMonthlyReading($meter, $dto->billingYear, $dto->billingMonth)) {
            throw new \InvalidArgumentException('Показание за указанный месяц уже существует.');
        }

        $reading = new MeterReading(
            meter: $meter,
            recordedByUser: $user,
            type: MeterReadingType::Monthly,
            value: $dto->value,
            billingYear: $dto->billingYear,
            billingMonth: $dto->billingMonth,
            comment: $dto->comment
        );

        $this->entityManager->persist($reading);
        $this->entityManager->flush();

        return $reading;
    }
}
