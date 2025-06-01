<?php

namespace App\Domain\User\Command;

use App\Domain\User\DTO\UpdateUserRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\UserRole;
use App\Domain\User\Exception\UserAlreadyExistsException;
use App\Domain\User\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UpdateUserHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function handle(User $user, UpdateUserRequest $request): User
    {
        if ($request->email !== null && $request->email !== $user->getEmail()) {
            $existingUserWithNewEmail = $this->userRepository->findByEmail($request->email);
            if ($existingUserWithNewEmail && $existingUserWithNewEmail->getId() !== $user->getId()) {
                throw new UserAlreadyExistsException($request->email);
            }
            $user->changeEmail($request->email);
        }

        if ($request->name !== null) {
            $user->changeName($request->name);
        }

        if ($request->password !== null && $request->password !== '') {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $request->password);
            $user->changePasswordHash($hashedPassword);
        }

        if ($request->role !== null) {
            $user->changeRole(UserRole::from($request->role));
        }

        $this->userRepository->save($user);

        return $user;
    }
}
