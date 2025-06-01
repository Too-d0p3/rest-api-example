<?php

namespace App\Domain\Article\DTO;

use App\Shared\DTO\Dto;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateArticleRequest implements Dto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 5, max: 255)]
        public string $title,

        #[Assert\NotBlank]
        #[Assert\Length(min: 10)]
        public string $content,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            title: $data['title'] ?? '',
            content: $data['content'] ?? '',
        );
    }
}
