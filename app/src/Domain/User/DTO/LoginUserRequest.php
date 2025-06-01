<?php

namespace App\Domain\User\DTO;

use App\Shared\DTO\Dto;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class LoginUserRequest implements Dto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,

        #[Assert\NotBlank]
        public readonly string $password,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            email: $data['email'] ?? '',
            password: $data['password'] ?? ''
        );
    }
}