<?php

namespace App\Property\Domain\Entity;

use App\Property\Domain\Enum\PropertyStatus;
use App\Property\Domain\Enum\PropertyType;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PropertyRepository::class)]
#[ORM\Table(name: 'properties')]
class Property
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(enumType: PropertyType::class, length: 50)]
    private PropertyType $typeCode;

    #[ORM\Column(enumType: PropertyStatus::class, length: 50)]
    private PropertyStatus $status;

    #[ORM\Column(length: 255)]
    private string $address;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    
    #[ORM\OneToMany(mappedBy: 'property', targetEntity: PropertyMember::class, cascade: ['persist'], orphanRemoval: false)]
    private Collection $members;

    public function __construct(
        string $title = '',
        ?PropertyType $typeCode = null,
        string $address = '',
        ?string $description = null,
        array $metadata = []
    ) {
        $this->id = Uuid::v7();
        $this->title = $title !== '' ? $title : 'Новый объект';
        $this->typeCode = $typeCode ?? PropertyType::Apartment;
        $this->status = PropertyStatus::Active;
        $this->address = $address;
        $this->description = $description;
        $this->metadata = $metadata;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->members = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setTypeCode(PropertyType $typeCode): void
    {
        $this->typeCode = $typeCode;
    }

    public function setStatus(PropertyStatus $status): void
    {
        $this->status = $status;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getTypeCode(): PropertyType
    {
        return $this->typeCode;
    }

    public function getStatus(): PropertyStatus
    {
        return $this->status;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function addMember(PropertyMember $member): void
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
        }
    }

    
    public function getMembers(): Collection
    {
        return $this->members;
    }
}
