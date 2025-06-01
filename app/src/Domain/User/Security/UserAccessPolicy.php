<?php

namespace App\Domain\User\Security;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\UserRole;
use App\Shared\Security\EntityAccessPolicy;

class UserAccessPolicy implements EntityAccessPolicy
{
    public function canViewList(User $user): bool
    {
        return $user->getRole() === UserRole::ADMIN;
    }

    public function canViewDetail(User $user, object $subject): bool
    {
        return $subject instanceof User && $user->getRole() === UserRole::ADMIN;
    }

    public function canCreate(User $user): bool
    {
        return $user->getRole() === UserRole::ADMIN;
    }

    public function canEdit(User $user, object $subject): bool
    {
        return $subject instanceof User &&
            ($user->getRole() === UserRole::ADMIN);
    }

    public function canDelete(User $user, object $subject): bool
    {
        return $this->canEdit($user, $subject);
    }
}