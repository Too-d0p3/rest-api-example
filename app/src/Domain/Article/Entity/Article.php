<?php

namespace App\Domain\Article\Entity;

use App\Domain\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'articles')]
class Article
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['article:read', 'article:list'])]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['article:read', 'article:list', 'article:write'])]
    private string $title;

    #[ORM\Column(type: 'text')]
    #[Groups(['article:read', 'article:write'])]
    private string $content;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['article:read'])]
    private User $author;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['article:read', 'article:list'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['article:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Uuid $id,
        string $title,
        string $content,
        User $author,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->author = $author;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function create(string $title, string $content, User $author): self
    {
        $now = new \DateTimeImmutable();
        return new self(
            Uuid::v4(),
            $title,
            $content,
            $author,
            $now,
            $now,
        );
    }

    public function changeTitle(string $newTitle): void
    {
        if ($this->title !== $newTitle) {
            $this->title = $newTitle;
            $this->touchUpdatedAt();
        }
    }

    public function changeContent(string $newContent): void
    {
        if ($this->content !== $newContent) {
            $this->content = $newContent;
            $this->touchUpdatedAt();
        }
    }

    private function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
