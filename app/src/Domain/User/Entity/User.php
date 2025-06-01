<?php

namespace App\Domain\User\Entity;

use App\Domain\User\Enum\UserRole;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements PasswordAuthenticatedUserInterface, UserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['user:read', 'user:list', 'article:read'])]
    private Uuid $id;

    #[ORM\Column(type: 'string', unique: true)]
    #[Groups(['user:read', 'user:list', 'article:read'])]
    private string $email;

    #[ORM\Column(type: 'string')]
    #[Groups(['user:read', 'user:list', 'article:read'])]
    private string $name;

    #[ORM\Column(type: 'string')]
    private string $passwordHash;

    #[ORM\Column(type: 'string', enumType: UserRole::class)]
    #[Groups(['user:read', 'user:list', 'article:read'])]
    private UserRole $role;

    public function __construct(
        Uuid $id,
        string $email,
        string $name,
        string $passwordHash,
        UserRole $role
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
    }

    public static function register(string $email, string $name, string $passwordHash, UserRole $role): self
    {
        return new self(Uuid::v4(), $email, $name, $passwordHash, $role);
    }

    public function changeEmail(string $newEmail): void
    {
        // Zde by mohla být i dodatečná validace emailu, pokud je potřeba
        $this->email = $newEmail;
    }

    public function changeName(string $newName): void
    {
        $this->name = $newName;
    }

    public function changeRole(UserRole $newRole): void
    {
        $this->role = $newRole;
    }

    public function changePasswordHash(string $newPasswordHash): void
    {
        $this->passwordHash = $newPasswordHash;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isAuthor(): bool
    {
        return $this->role === UserRole::AUTHOR;
    }

    public function isReader(): bool
    {
        return $this->role === UserRole::READER;
    }

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }

    /**
     * @see UserInterface
     */
    #[Groups(["user:read"])]
    public function getRoles(): array
    {
        return [$this->role->value];
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials()
    {
        // Pokud bys měl vlastnost pro plain password, zde bys ji nuloval.
        // $this->plainPassword = null;
        // V tomto případě není co mazat, ale je dobré mít metodu implementovanou.
    }
}
