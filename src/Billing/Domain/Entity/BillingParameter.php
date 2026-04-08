<?php

namespace App\Billing\Domain\Entity;

use App\Billing\Domain\Enum\BillingCategory;
use App\Billing\Domain\Enum\BillingParameterSourceType;
use App\Billing\Infrastructure\Persistence\Doctrine\BillingParameterRepository;
use App\Property\Domain\Entity\Property;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BillingParameterRepository::class)]
#[ORM\Table(name: 'billing_parameters')]
class BillingParameter
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Property::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Property $property;

    #[ORM\ManyToOne(targetEntity: Meter::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Meter $meter;

    #[ORM\Column(length: 100)]
    private string $code;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(enumType: BillingCategory::class, length: 50)]
    private BillingCategory $category;

    #[ORM\Column(enumType: BillingParameterSourceType::class, length: 50)]
    private BillingParameterSourceType $sourceType;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $unit;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Property $property,
        ?Meter $meter,
        string $code,
        string $title,
        BillingCategory $category,
        BillingParameterSourceType $sourceType,
        ?string $unit = null
    ) {
        $this->id = Uuid::v7();
        $this->property = $property;
        $this->meter = $meter;
        $this->code = mb_strtolower($code);
        $this->title = $title;
        $this->category = $category;
        $this->sourceType = $sourceType;
        $this->unit = $unit;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getProperty(): Property
    {
        return $this->property;
    }

    public function getMeter(): ?Meter
    {
        return $this->meter;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCategory(): BillingCategory
    {
        return $this->category;
    }

    public function getSourceType(): BillingParameterSourceType
    {
        return $this->sourceType;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
