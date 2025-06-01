<?php

namespace App\Domain\User\DTO;

use App\Domain\User\Enum\UserRole;
use App\Shared\DTO\Dto;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterUserRequest implements Dto
{

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 50)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Length(min: 6)]
        public string $password,

        #[Assert\NotBlank]
        #[Assert\Choice(callback: [UserRole::class, 'values'])]
        public string $role
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            email: $data['email'] ?? '',
            name: $data['name'] ?? '',
            password: $data['password'] ?? '',
            role: $data['role'] ?? '',
        );
    }
}