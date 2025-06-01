<?php

namespace App\Shared\Security;

use App\Domain\User\Entity\User;

interface EntityAccessPolicy
{
    public function canViewList(User $user): bool;
    public function canViewDetail(User $user, object $subject): bool;
    public function canCreate(User $user): bool;
    public function canEdit(User $user, object $subject): bool;
    public function canDelete(User $user, object $subject): bool;
}