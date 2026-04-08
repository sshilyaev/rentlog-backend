<?php

namespace App\Billing\Application\Service;

use App\Billing\Application\Dto\CreateBillingParameterRequestDto;
use App\Billing\Domain\Entity\BillingParameter;
use App\Billing\Domain\Entity\Meter;
use App\Billing\Domain\Enum\BillingCategory;
use App\Billing\Domain\Enum\BillingParameterSourceType;
use App\Billing\Infrastructure\Persistence\Doctrine\BillingParameterRepository;
use App\Property\Domain\Entity\Property;
use Doctrine\ORM\EntityManagerInterface;

final class CreateBillingParameterService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BillingParameterRepository $billingParameterRepository
    ) {
    }

    public function handle(Property $property, ?Meter $meter, CreateBillingParameterRequestDto $dto): BillingParameter
    {
        if ($this->billingParameterRepository->existsByCode($property, $dto->code)) {
            throw new \InvalidArgumentException('Параметр начисления с таким кодом уже существует для объекта.');
        }

        $sourceType = BillingParameterSourceType::from($dto->sourceType);

        if ($sourceType === BillingParameterSourceType::Meter && !$meter instanceof Meter) {
            throw new \InvalidArgumentException('Для параметра типа meter нужно указать связанный счетчик.');
        }

        if ($sourceType === BillingParameterSourceType::Fixed && $meter instanceof Meter) {
            throw new \InvalidArgumentException('Для фиксированного параметра счетчик не нужен.');
        }

        $parameter = new BillingParameter(
            property: $property,
            meter: $meter,
            code: $dto->code,
            title: $dto->title,
            category: BillingCategory::from($dto->category),
            sourceType: $sourceType,
            unit: $dto->unit
        );

        $this->entityManager->persist($parameter);
        $this->entityManager->flush();

        return $parameter;
    }
}
