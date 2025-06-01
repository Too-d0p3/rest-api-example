<?php

namespace App\Tests\Helper;

use App\Domain\User\Enum\UserRole;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

class UserTestHelper
{
    public function __construct(private KernelBrowser $client) {}

    public function registerUser(
        string $email,
        string $password = 'password123',
        string $role = 'author',
        string $name = 'Test User',
    ): void {
        $role = UserRole::from($role);

        $this->client->request('POST', '/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'name' => $name,
            'password' => $password,
            'role' => $role->value,
        ]));

        if ($this->client->getResponse()->getStatusCode() !== Response::HTTP_CREATED) {
            throw new \RuntimeException('User registration failed in test: ' . $this->client->getResponse()->getContent());
        }
    }

    public function loginAndGetToken(string $email, string $password): string
    {
        $this->client->request('POST', '/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
        ]));

        $data = json_decode($this->client->getResponse()->getContent(), true);

        if (!isset($data['token'])) {
            throw new \RuntimeException('Login failed in test: ' . $this->client->getResponse()->getContent());
        }

        return $data['token'];
    }

    public function registerAndLogin($email, $role){
        $this->registerUser(email: $email, role: $role, password: 'password123');
        return $this->loginAndGetToken($email, 'password123');
    }

    public function getUserId($email, $password){
        $token = $this->loginAndGetToken($email, $password);

        $this->client->request('GET', '/auth/me', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer $token",
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        if(!isset($data['id'])) {
            throw new \RuntimeException('Login failed in test: ' . $this->client->getResponse()->getContent());
        }

        return $data['id'];
    }

    public function registerUserAndGetId(
        string $email,
        string $password = 'password123',
        string $role = 'author',
        string $name = 'Test User',
        ?string $adminTokenForUserCreation = null // Optional token if user creation is protected
    ): string {
        $roleEnum = UserRole::from($role);

        $headers = ['CONTENT_TYPE' => 'application/json'];
        $endpoint = '/auth/register'; // Default registration endpoint

        // If an admin token is provided, assume we are creating user via /users endpoint as admin
        if ($adminTokenForUserCreation) {
            $headers['HTTP_AUTHORIZATION'] = "Bearer $adminTokenForUserCreation";
            $endpoint = '/users';
        }

        $this->client->request('POST', $endpoint, [], [], $headers, json_encode([
            'email' => $email,
            'name' => $name,
            'password' => $password,
            'role' => $roleEnum->value,
        ]));

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        if ($response->getStatusCode() !== Response::HTTP_CREATED || !isset($responseData['id'])) {
            throw new \RuntimeException(
                sprintf(
                    'User creation/registration for %s failed in test (Status: %s, Response: %s)',
                    $email,
                    $response->getStatusCode(),
                    $response->getContent()
                )
            );
        }
        return $responseData['id'];
    }
}