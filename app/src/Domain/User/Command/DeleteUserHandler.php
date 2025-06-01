<?php

namespace App\Domain\User\Command;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepository;

final class DeleteUserHandler
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function handle(User $user): void
    {
        $this->userRepository->delete($user);
    }
}
