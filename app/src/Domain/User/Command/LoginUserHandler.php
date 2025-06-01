<?php

namespace App\Domain\User\Command;

use App\Domain\User\DTO\LoginUserRequest;
use App\Domain\User\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

readonly class LoginUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $tokenManager,
    ) {}

    public function handle(LoginUserRequest $request): string
    {
        $user = $this->userRepository->findByEmail($request->email);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $request->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        return $this->tokenManager->create($user);
    }
}