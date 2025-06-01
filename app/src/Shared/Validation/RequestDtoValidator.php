<?php

namespace App\Shared\Validation;

use App\Shared\Exception\ValidationProblemException;

final class RequestDtoValidator
{
    public function __construct(private DtoValidator $dtoValidator) {}

    /**
     * Validates the DTO and throws a ValidationProblemException if validation fails.
     *
     * @param object $dto The Data Transfer Object to validate.
     * @throws ValidationProblemException If validation fails.
     */
    public function validate(object $dto): void
    {
        try {
            $this->dtoValidator->validate($dto);
        } catch (ValidationException $e) {
            throw new ValidationProblemException($e->errors, 'Validation failed', 0, $e);
        }
    }
}