<?php

namespace App\Shared\Validation;

use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DtoValidator
{
    public function __construct(private ValidatorInterface $validator) {}

    /**
     * @throws ValidationException
     */
    public function validate(object $dto): void
    {
        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            throw ValidationException::fromViolations($violations);
        }
    }
}
