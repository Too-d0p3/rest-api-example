<?php

namespace App\Domain\User\DTO;

use App\Domain\User\Enum\UserRole;
use App\Shared\DTO\Dto;
use Symfony\Component\Validator\Constraints as Assert;

readonly class UpdateUserRequest implements Dto
{
    public function __construct(
        #[Assert\Email]
        public ?string $email = null,

        #[Assert\NotBlank]
        public ?string $name = null,

        #[Assert\Length(min: 6)]
        public ?string $password = null,

        #[Assert\Choice(callback: [UserRole::class, 'values'])]
        public ?string $role = null,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            email: $data['email'] ?? null,
            name: $data['name'] ?? null,
            password: $data['password'] ?? null,
            role: $data['role'] ?? null,
        );
    }
}
