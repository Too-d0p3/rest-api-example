<?php

namespace App\Domain\Article\Command;

use App\Domain\Article\DTO\CreateArticleRequest;
use App\Domain\Article\Entity\Article;
use App\Domain\Article\Repository\ArticleRepository;
use App\Domain\User\Entity\User;

readonly class CreateArticleHandler
{
    public function __construct(
        private ArticleRepository $repository,
    ) {}

    public function handle(CreateArticleRequest $dto, User $author): Article
    {
        $article = Article::create(
            title: $dto->title,
            content: $dto->content,
            author: $author,
        );

        $this->repository->save($article);

        return $article;
    }
}
