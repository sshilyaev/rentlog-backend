<?php

namespace App\Billing\Application\Service;

use App\Billing\Application\Dto\CreateTariffPeriodRequestDto;
use App\Billing\Domain\Entity\BillingParameter;
use App\Billing\Domain\Entity\TariffPeriod;
use App\Billing\Domain\Enum\TariffPricingType;
use App\Billing\Infrastructure\Persistence\Doctrine\TariffPeriodRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CreateTariffPeriodService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TariffPeriodRepository $tariffPeriodRepository
    ) {
    }

    public function handle(BillingParameter $billingParameter, CreateTariffPeriodRequestDto $dto): TariffPeriod
    {
        $effectiveFrom = new \DateTimeImmutable($dto->effectiveFrom);
        $effectiveTo = $dto->effectiveTo !== null ? new \DateTimeImmutable($dto->effectiveTo) : null;

        if ($effectiveTo !== null && $effectiveTo < $effectiveFrom) {
            throw new \InvalidArgumentException('Дата окончания не может быть раньше даты начала.');
        }

        if ($this->tariffPeriodRepository->hasOverlap($billingParameter, $effectiveFrom, $effectiveTo)) {
            throw new \InvalidArgumentException('Тарифный период пересекается с уже существующим периодом.');
        }

        $tariffPeriod = new TariffPeriod(
            billingParameter: $billingParameter,
            pricingType: TariffPricingType::from($dto->pricingType),
            price: $dto->price,
            currency: $dto->currency,
            effectiveFrom: $effectiveFrom,
            effectiveTo: $effectiveTo
        );

        $this->entityManager->persist($tariffPeriod);
        $this->entityManager->flush();

        return $tariffPeriod;
    }
}
