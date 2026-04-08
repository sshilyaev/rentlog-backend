<?php

namespace App\Billing\Domain\Entity;

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

    #[ORM\Column(length: 50)]
    private string $unit;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Property $property, string $code, string $title, string $unit)
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUnit(): string
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
