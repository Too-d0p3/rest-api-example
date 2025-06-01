<?php
namespace App\Shared\DTO;

interface Dto
{
    public static function fromArray(array $data): static;
}