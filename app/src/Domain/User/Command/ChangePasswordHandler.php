<?php

namespace App\Domain\User\Command;

use App\Domain\User\DTO\ChangePasswordRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Exception\InvalidOldPasswordException;
use App\Domain\User\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ChangePasswordHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function handle(User $user, ChangePasswordRequest $request): User
    {
        if (!$this->passwordHasher->isPasswordValid($user, $request->oldPassword)) {
            throw new InvalidOldPasswordException();
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $request->newPassword);
        $user->changePasswordHash($hashedPassword);
        $this->userRepository->save($user);

        return $user;
    }
}
