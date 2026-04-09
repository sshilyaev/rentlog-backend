<?php

namespace App\Property\Domain\Entity;

use App\Auth\Domain\Entity\User;
use App\Property\Infrastructure\Persistence\Doctrine\InvitationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: InvitationRepository::class)]
#[ORM\Table(name: 'invitations')]
class Invitation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 64, unique: true)]
    private string $code;

    #[ORM\ManyToOne(targetEntity: Property::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Property $property;

    #[ORM\ManyToOne(targetEntity: PropertyMember::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private PropertyMember $propertyMember;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $createdBy;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $targetEmail;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $claimedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Property $property,
        PropertyMember $propertyMember,
        User $createdBy,
        ?string $targetEmail,
        \DateTimeImmutable $expiresAt
    ) {
        $this->id = Uuid::v7();
        $this->code = strtoupper(bin2hex(random_bytes(8)));
        $this->property = $property;
        $this->propertyMember = $propertyMember;
        $this->createdBy = $createdBy;
        $this->targetEmail = $targetEmail !== null ? mb_strtolower($targetEmail) : null;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getProperty(): Property
    {
        return $this->property;
    }

    public function getPropertyMember(): PropertyMember
    {
        return $this->propertyMember;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function __toString(): string
    {
        return $this->code;
    }

    public function getTargetEmail(): ?string
    {
        return $this->targetEmail;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getClaimedAt(): ?\DateTimeImmutable
    {
        return $this->claimedAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new \DateTimeImmutable();
    }

    public function isClaimed(): bool
    {
        return $this->claimedAt !== null;
    }

    public function markClaimed(): void
    {
        $this->claimedAt = new \DateTimeImmutable();
    }
}
