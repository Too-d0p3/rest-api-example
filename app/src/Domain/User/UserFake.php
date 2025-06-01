<?php

namespace App\Domain\User;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserFake implements PasswordAuthenticatedUserInterface
{
    public function getPassword(): ?string
    {
        return null;
    }
}