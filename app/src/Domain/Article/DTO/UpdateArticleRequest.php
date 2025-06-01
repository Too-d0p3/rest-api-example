<?php

namespace App\Domain\Article\DTO;

use App\Shared\DTO\Dto;
use Symfony\Component\Validator\Constraints as Assert;


final readonly class UpdateArticleRequest implements Dto
{
    public function __construct(
        #[Assert\Length(min: 5, max: 255)]
        public string $title,

        #[Assert\Length(min: 10)]
        public string $content,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            title: $data['title'] ?? null,
            content: $data['content'] ?? null,
        );
    }
}