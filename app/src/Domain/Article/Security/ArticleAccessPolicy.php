<?php

namespace App\Domain\Article\Security;

use App\Domain\Article\Entity\Article;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\UserRole;
use App\Shared\Security\EntityAccessPolicy;

class ArticleAccessPolicy implements EntityAccessPolicy
{
    public function canViewList(User $user): bool
    {
        return true;
    }

    public function canViewDetail(User $user, object $subject): bool
    {
        return $subject instanceof Article;
    }

    public function canCreate(User $user): bool
    {
        return in_array($user->getRole(), [UserRole::ADMIN, UserRole::AUTHOR], true);
    }

    public function canEdit(User $user, object $subject): bool
    {
        return $subject instanceof Article &&
            ($user->getRole() === UserRole::ADMIN || $subject->getAuthor()->getId() === $user->getId());
    }

    public function canDelete(User $user, object $subject): bool
    {
        return $this->canEdit($user, $subject);
    }
}