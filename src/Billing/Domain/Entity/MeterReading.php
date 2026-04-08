<?php

namespace App\Billing\Domain\Entity;

use App\Auth\Domain\Entity\User;
use App\Billing\Domain\Enum\MeterReadingType;
use App\Billing\Infrastructure\Persistence\Doctrine\MeterReadingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MeterReadingRepository::class)]
#[ORM\Table(name: 'meter_readings')]
class MeterReading
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Meter::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Meter $meter;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $recordedByUser;

    #[ORM\Column(enumType: MeterReadingType::class, length: 50)]
    private MeterReadingType $type;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $billingYear;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $billingMonth;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private string $value;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment;

    #[ORM\Column]
    private \DateTimeImmutable $recordedAt;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Meter $meter,
        User $recordedByUser,
        MeterReadingType $type,
        string $value,
        ?int $billingYear = null,
        ?int $billingMonth = null,
        ?string $comment = null
    ) {
        $this->id = Uuid::v7();
        $this->meter = $meter;
        $this->recordedByUser = $recordedByUser;
        $this->type = $type;
        $this->billingYear = $billingYear;
        $this->billingMonth = $billingMonth;
        $this->value = $value;
        $this->comment = $comment;
        $this->recordedAt = new \DateTimeImmutable();
        $this->createdAt = $this->recordedAt;
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getMeter(): Meter
    {
        return $this->meter;
    }

    public function getRecordedByUser(): User
    {
        return $this->recordedByUser;
    }

    public function getType(): MeterReadingType
    {
        return $this->type;
    }

    public function getBillingYear(): ?int
    {
        return $this->billingYear;
    }

    public function getBillingMonth(): ?int
    {
        return $this->billingMonth;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getRecordedAt(): \DateTimeImmutable
    {
        return $this->recordedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
