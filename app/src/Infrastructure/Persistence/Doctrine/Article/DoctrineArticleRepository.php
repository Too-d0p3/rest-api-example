<?php

namespace App\Infrastructure\Persistence\Doctrine\Article;

use App\Domain\Article\Entity\Article;
use App\Domain\Article\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineArticleRepository implements ArticleRepository
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function save(Article $article): void
    {
        $this->em->persist($article);
        $this->em->flush();
    }

    public function findById(string $id): ?Article
    {
        return $this->em->getRepository(Article::class)->find($id);
    }

    public function findAll(): array
    {
        return $this->em->getRepository(Article::class)->findAll();
    }

    public function delete(Article $article): void
    {
        $this->em->remove($article);
        $this->em->flush();
    }
}
