<?php

namespace App\Shared\DTO;

use Symfony\Component\HttpFoundation\Request;

/**
 * @template T of Dto
 */
class RequestDtoResolver
{
    /**
     * @template T of Dto
     * @param class-string<T> $class
     * @return T
     */
    public function resolve(string $class, Request $request): Dto
    {
        $data = json_decode($request->getContent(), true);

        if (!is_subclass_of($class, Dto::class)) {
            throw new \InvalidArgumentException("Class $class must implement RequestDtoInterface.");
        }

        /** @var T $dto */
        return $class::fromArray($data);
    }
}