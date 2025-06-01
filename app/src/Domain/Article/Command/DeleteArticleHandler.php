<?php

namespace App\Domain\Article\Command;

use App\Domain\Article\Entity\Article;
use App\Domain\Article\Repository\ArticleRepository;

readonly class DeleteArticleHandler
{
    public function __construct(
        private ArticleRepository $repository,
    ) {}

    public function handle(Article $article): void
    {
        $this->repository->delete($article);
    }
}
