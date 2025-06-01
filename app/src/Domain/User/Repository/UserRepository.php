<?php

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\User;

interface UserRepository
{
    public function save(User $user): void;
    public function findByEmail(string $email): ?User;
    public function findById(string $uuid): ?User;
    public function findAll(): array;
    public function delete(User $user): void;
}