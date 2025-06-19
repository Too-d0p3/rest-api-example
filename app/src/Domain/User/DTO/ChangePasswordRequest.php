<?php

namespace App\Domain\User\DTO;

use App\Shared\DTO\Dto;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangePasswordRequest implements Dto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 6)]
        public string $oldPassword,

        #[Assert\NotBlank]
        #[Assert\Length(min: 6)]
        public string $newPassword,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            oldPassword: $data['oldPassword'] ?? '',
            newPassword: $data['newPassword'] ?? ''
        );
    }
}
