<?php

namespace App\Rent\Domain\Entity;

use App\Property\Domain\Entity\Property;
use App\Property\Domain\Entity\PropertyMember;
use App\Rent\Domain\Enum\RentTermsStatus;
use App\Rent\Infrastructure\Persistence\Doctrine\RentTermsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RentTermsRepository::class)]
#[ORM\Table(name: 'rent_terms')]
class RentTerms
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Property::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Property $property;

    #[ORM\ManyToOne(targetEntity: PropertyMember::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?PropertyMember $propertyMember;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $baseRentAmount;

    #[ORM\Column(length: 3)]
    private string $currency;

    #[ORM\Column(type: 'smallint')]
    private int $billingDay;

    #[ORM\Column]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endsAt;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    #[ORM\Column(enumType: RentTermsStatus::class, length: 50)]
    private RentTermsStatus $status;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Property $property,
        ?PropertyMember $propertyMember,
        string $baseRentAmount,
        string $currency,
        int $billingDay,
        \DateTimeImmutable $startsAt,
        ?\DateTimeImmutable $endsAt = null,
        ?string $notes = null,
        RentTermsStatus $status = RentTermsStatus::Active
    ) {
        $this->id = Uuid::v7();
        $this->property = $property;
        $this->propertyMember = $propertyMember;
        $this->baseRentAmount = $baseRentAmount;
        $this->currency = mb_strtoupper($currency);
        $this->billingDay = $billingDay;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->notes = $notes;
        $this->status = $status;
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

    public function getPropertyMember(): ?PropertyMember
    {
        return $this->propertyMember;
    }

    public function getBaseRentAmount(): string
    {
        return $this->baseRentAmount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getBillingDay(): int
    {
        return $this->billingDay;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getStatus(): RentTermsStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(
        string $baseRentAmount,
        string $currency,
        int $billingDay,
        \DateTimeImmutable $startsAt,
        ?\DateTimeImmutable $endsAt,
        ?string $notes,
        RentTermsStatus $status
    ): void {
        $this->baseRentAmount = $baseRentAmount;
        $this->currency = mb_strtoupper($currency);
        $this->billingDay = $billingDay;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->notes = $notes;
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
