<?php

namespace App\Shared\Validation;

use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ValidationException extends \RuntimeException
{
    public readonly array $errors;

    public static function fromViolations(ConstraintViolationListInterface $violations): self
    {
        $errors = [];

        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        $e = new self('Validation failed.');
        $e->errors = $errors;
        return $e;
    }
}