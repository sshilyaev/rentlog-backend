<?php

namespace App\Billing\Domain\Entity;

use App\Billing\Domain\Enum\TariffPricingType;
use App\Billing\Infrastructure\Persistence\Doctrine\TariffPeriodRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TariffPeriodRepository::class)]
#[ORM\Table(name: 'tariff_periods')]
class TariffPeriod
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: BillingParameter::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private BillingParameter $billingParameter;

    #[ORM\Column(enumType: TariffPricingType::class, length: 50)]
    private TariffPricingType $pricingType;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $price;

    #[ORM\Column(length: 3)]
    private string $currency;

    #[ORM\Column]
    private \DateTimeImmutable $effectiveFrom;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $effectiveTo;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        BillingParameter $billingParameter,
        TariffPricingType $pricingType,
        string $price,
        string $currency,
        \DateTimeImmutable $effectiveFrom,
        ?\DateTimeImmutable $effectiveTo = null
    ) {
        $this->id = Uuid::v7();
        $this->billingParameter = $billingParameter;
        $this->pricingType = $pricingType;
        $this->price = $price;
        $this->currency = mb_strtoupper($currency);
        $this->effectiveFrom = $effectiveFrom;
        $this->effectiveTo = $effectiveTo;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getBillingParameter(): BillingParameter
    {
        return $this->billingParameter;
    }

    public function setBillingParameter(BillingParameter $billingParameter): void
    {
        $this->billingParameter = $billingParameter;
    }

    public function __toString(): string
    {
        return $this->price.' '.$this->currency.' с '.$this->effectiveFrom->format('Y-m-d');
    }

    public function setPricingType(TariffPricingType $pricingType): void
    {
        $this->pricingType = $pricingType;
    }

    public function setPrice(string $price): void
    {
        $this->price = $price;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = mb_strtoupper($currency);
    }

    public function setEffectiveFrom(\DateTimeImmutable $effectiveFrom): void
    {
        $this->effectiveFrom = $effectiveFrom;
    }

    public function setEffectiveTo(?\DateTimeImmutable $effectiveTo): void
    {
        $this->effectiveTo = $effectiveTo;
    }

    public function getPricingType(): TariffPricingType
    {
        return $this->pricingType;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getEffectiveFrom(): \DateTimeImmutable
    {
        return $this->effectiveFrom;
    }

    public function getEffectiveTo(): ?\DateTimeImmutable
    {
        return $this->effectiveTo;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
