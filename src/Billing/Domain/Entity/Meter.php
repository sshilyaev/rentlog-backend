<?php

namespace App\Billing\Domain\Entity;

use App\Billing\Domain\Enum\MeterUnit;
use App\Billing\Infrastructure\Persistence\Doctrine\MeterRepository;
use App\Property\Domain\Entity\Property;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MeterRepository::class)]
#[ORM\Table(name: 'meters')]
class Meter
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Property::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Property $property;

    #[ORM\Column(length: 100)]
    private string $code;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(enumType: MeterUnit::class, length: 50)]
    private MeterUnit $unit;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Property $property, string $code, string $title, MeterUnit $unit)
    {
        $this->id = Uuid::v7();
        $this->property = $property;
        $this->code = mb_strtolower($code);
        $this->title = $title;
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

    public function setProperty(Property $property): void
    {
        $this->property = $property;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->title.' ('.$this->code.')';
    }

    public function setCode(string $code): void
    {
        $this->code = mb_strtolower($code);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setUnit(MeterUnit $unit): void
    {
        $this->unit = $unit;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUnit(): MeterUnit
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
