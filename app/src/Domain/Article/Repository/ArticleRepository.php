<?php

namespace App\Domain\Article\Repository;

use App\Domain\Article\Entity\Article;

interface ArticleRepository
{
    public function save(Article $article): void;

    public function findById(string $id): ?Article;

    public function findAll(): array;

    public function delete(Article $article): void;
}