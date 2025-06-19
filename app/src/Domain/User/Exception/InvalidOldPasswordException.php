<?php

namespace App\Domain\User\Exception;

use RuntimeException;

final class InvalidOldPasswordException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Old password is incorrect.');
    }
}
