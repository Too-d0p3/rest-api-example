<?php

namespace App\Domain\User\Exception;

use RuntimeException;

final class UserAlreadyExistsException extends RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct("User with email '$email' already exists.");
    }
}