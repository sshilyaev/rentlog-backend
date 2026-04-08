<?php

namespace App\Property\Domain\Entity;

use App\Auth\Domain\Entity\User;
use App\Property\Domain\Enum\PropertyMemberRole;
use App\Property\Domain\Enum\PropertyMemberStatus;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PropertyMemberRepository::class)]
#[ORM\Table(name: 'property_members')]
class PropertyMember
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Property::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Property $property;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\Column(enumType: PropertyMemberRole::class, length: 50)]
    private PropertyMemberRole $role;

    #[ORM\Column(enumType: PropertyMemberStatus::class, length: 50)]
    private PropertyMemberStatus $status;

    #[ORM\Column(length: 255)]
    private string $fullName;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Property $property,
        ?User $user,
        PropertyMemberRole $role,
        PropertyMemberStatus $status,
        string $fullName,
        ?string $email = null,
        ?string $phone = null
    ) {
        $this->id = Uuid::v7();
        $this->property = $property;
        $this->user = $user;
        $this->role = $role;
        $this->status = $status;
        $this->fullName = $fullName;
        $this->email = $email !== null ? mb_strtolower($email) : null;
        $this->phone = $phone;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->property->addMember($this);
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getProperty(): Property
    {
        return $this->property;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getRole(): PropertyMemberRole
    {
        return $this->role;
    }

    public function getStatus(): PropertyMemberStatus
    {
        return $this->status;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function linkUser(User $user): void
    {
        $this->user = $user;
        $this->email = $user->getEmail();
        $this->fullName = $user->getFullName();
        $this->status = PropertyMemberStatus::Active;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
