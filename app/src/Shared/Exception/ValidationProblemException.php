<?php

namespace App\Shared\Exception;

class ValidationProblemException extends \RuntimeException
{
    private array $validationErrors;

    public function __construct(array $validationErrors, string $message = "Validation failed", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->validationErrors = $validationErrors;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
} 