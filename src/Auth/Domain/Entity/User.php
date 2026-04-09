<?php

namespace App\Auth\Domain\Entity;

use App\Auth\Infrastructure\Persistence\Doctrine\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $password;

    #[ORM\Column(length: 255)]
    private string $fullName;

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordResetExpiresAt = null;

    public ?string $plainPassword = null;

    public function __construct(string $email = '', string $password = '', string $fullName = '')
    {
        $this->id = Uuid::v7();
        $this->email = $email !== '' ? mb_strtolower($email) : '';
        $this->password = $password;
        $this->fullName = $fullName;
    }

    public function __toString(): string
    {
        return $this->email !== '' ? $this->email : $this->id->toRfc4122();
    }

    public function setEmail(string $email): void
    {
        $this->email = mb_strtolower($email);
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    
    public function setRoles(array $roles): void
    {
        $this->roles = array_values(array_unique($roles));
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function eraseCredentials(): void
    {
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function updatePassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function verifyEmail(): void
    {
        $this->emailVerifiedAt = new \DateTimeImmutable();
        $this->emailVerificationToken = null;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $emailVerificationToken): void
    {
        $this->emailVerificationToken = $emailVerificationToken;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function getPasswordResetExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetExpiresAt;
    }

    public function setPasswordResetRequest(?string $token, ?\DateTimeImmutable $expiresAt): void
    {
        $this->passwordResetToken = $token;
        $this->passwordResetExpiresAt = $expiresAt;
    }

    public function clearPasswordResetRequest(): void
    {
        $this->passwordResetToken = null;
        $this->passwordResetExpiresAt = null;
    }
}
