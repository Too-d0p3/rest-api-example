<?php

namespace App\Domain\User\Command;

use App\Domain\User\DTO\RegisterUserRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\UserRole;
use App\Domain\User\Exception\UserAlreadyExistsException;
use App\Domain\User\Repository\UserRepository;
use App\Domain\User\UserFake;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function handle(RegisterUserRequest $request): User
    {
        if ($this->userRepository->findByEmail($request->email)) {
            throw new UserAlreadyExistsException($request->email);
        }

        $hashedPassword = $this->passwordHasher->hashPassword(
            new UserFake(), // Temporary object for hashing context
            $request->password
        );

        $user = User::register(
            email: $request->email,
            name: $request->name,
            passwordHash: $hashedPassword,
            role: UserRole::from($request->role)
        );

        $this->userRepository->save($user);

        return $user;
    }
}