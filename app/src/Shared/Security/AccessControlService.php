<?php

namespace App\Shared\Security;

use App\Domain\User\Entity\User;

class AccessControlService
{
    public function __construct(private PolicyRegistry $registry) {}

    public function canViewList(User $user, string $entityClass): bool
    {
        return $this->registry->getPolicyByClass($entityClass)->canViewList($user);
    }

    public function canViewDetail(User $user, object $subject): bool
    {
        return $this->registry->getPolicyFor($subject)->canViewDetail($user, $subject);
    }

    public function canEdit(User $user, object $subject): bool
    {
        return $this->registry->getPolicyFor($subject)->canEdit($user, $subject);
    }

    public function canDelete(User $user, object $subject): bool
    {
        return $this->registry->getPolicyFor($subject)->canDelete($user, $subject);
    }

    public function canCreate(User $user, string $entityClass): bool
    {
        return $this->registry->getPolicyByClass($entityClass)->canCreate($user);
    }
}
