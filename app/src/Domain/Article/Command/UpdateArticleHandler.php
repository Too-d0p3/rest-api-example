<?php

namespace App\Domain\Article\Command;

use App\Domain\Article\DTO\UpdateArticleRequest;
use App\Domain\Article\Entity\Article;
use App\Domain\Article\Repository\ArticleRepository;

readonly class UpdateArticleHandler
{
    public function __construct(
        private ArticleRepository $repository,
    ) {}

    public function handle(Article $article, UpdateArticleRequest $dto): Article
    {
        if ($dto->title !== null) {
            $article->changeTitle($dto->title);
        }

        if ($dto->content !== null) {
            $article->changeContent($dto->content);
        }

        $this->repository->save($article);

        return $article;
    }
}