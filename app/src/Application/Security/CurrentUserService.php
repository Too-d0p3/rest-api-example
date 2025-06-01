<?php

namespace App\Application\Security;

use App\Domain\User\Entity\User;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Security;

readonly class CurrentUserService
{
    public function __construct(
        private Security $security
    ) {}

    public function getUser(): User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AuthenticationCredentialsNotFoundException('User is not authenticated.');
        }

        return $user;
    }
}
