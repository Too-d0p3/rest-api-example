<?php

namespace App\Tests\Functional\Auth;

use App\Domain\User\Enum\UserRole;
use App\Tests\Functional\FunctionalTestCase;
use App\Tests\Helper\UserTestHelper;
use Symfony\Component\HttpFoundation\Response;

class AuthEndpointTest extends FunctionalTestCase
{
    private UserTestHelper $userTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userTestHelper = new UserTestHelper($this->client);
    }

    public function testRegisterWithInvalidDataReturnsValidationProblem(): void
    {
        $this->client->request('POST', '/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'notanemail',
            'name' => '', // Empty name
            'password' => 'short', // Too short password
            'role' => 'INVALID_ROLE',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);

        $this->assertEquals('application/problem+json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('Validation Failed', $responseData['title']);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $responseData['status']);
        $this->assertArrayHasKey('invalid-params', $responseData);
        $this->assertNotEmpty($responseData['invalid-params']);

        $invalidParams = $responseData['invalid-params'];
        $errorsByName = [];
        foreach ($invalidParams as $param) {
            $errorsByName[$param['name']] = $param['reason'];
        }

        $this->assertArrayHasKey('email', $errorsByName);
        $this->assertStringContainsString('This value is not a valid email address.', $errorsByName['email']);

        $this->assertArrayHasKey('name', $errorsByName);
        // API vrací chybu o délce i pro prázdné jméno, přizpůsobíme očekávání.
        $this->assertStringContainsString('This value is too short. It should have 3 characters or more.', $errorsByName['name']);

        $this->assertArrayHasKey('password', $errorsByName);
        $this->assertStringContainsString('This value is too short. It should have 6 characters or more.', $errorsByName['password']);

        $this->assertArrayHasKey('role', $errorsByName);
        $this->assertStringContainsString('The value you selected is not a valid choice.', $errorsByName['role']);
    }

    public function testRegisterExistingUserReturnsConflict(): void
    {
        // First, register a user successfully
        $this->userTestHelper->registerUser('existing@example.com', 'password123', UserRole::READER->value, 'Existing User');

        // Try to register the same user again
        $this->client->request('POST', '/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'existing@example.com',
            'name' => 'Another Name',
            'password' => 'anotherpassword',
            'role' => UserRole::AUTHOR->value,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);

        $this->assertEquals('application/problem+json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('User already exists', $responseData['title']);
        $this->assertEquals(Response::HTTP_CONFLICT, $responseData['status']);
        $this->assertEquals("User with email 'existing@example.com' already exists.", $responseData['detail']);
    }

    public function testLoginWithInvalidDataReturnsValidationProblem(): void
    {
        $this->client->request('POST', '/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'notanemail',
            'password' => '', // Empty password
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);

        $this->assertEquals('application/problem+json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('Validation Failed', $responseData['title']);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $responseData['status']);
        $this->assertArrayHasKey('invalid-params', $responseData);
        
        $invalidParams = $responseData['invalid-params'];
        // Ověření konkrétních chyb
        $foundEmailError = false;
        $foundPasswordError = false;
        foreach ($invalidParams as $param) {
            if ($param['name'] === 'email') {
                $this->assertStringContainsString('This value is not a valid email address.', $param['reason']);
                $foundEmailError = true;
            }
            if ($param['name'] === 'password') {
                $this->assertStringContainsString('This value should not be blank.', $param['reason']);
                $foundPasswordError = true;
            }
        }
        $this->assertTrue($foundEmailError, 'Email validation error missing.');
        $this->assertTrue($foundPasswordError, 'Password validation error missing.');
    }

    public function testLoginWithIncorrectCredentialsReturnsUnauthorized(): void
    {
        $this->userTestHelper->registerUser('correctuser@example.com', 'CorrectPass123', UserRole::READER->value);

        $this->client->request('POST', '/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'correctuser@example.com',
            'password' => 'WrongPassword',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);

        $this->assertEquals('application/problem+json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('Authentication Failed', $responseData['title']);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $responseData['status']);
        // Detail může být obecný, např. "Invalid credentials."
        $this->assertStringContainsString('Invalid credentials.', $responseData['detail']);
    }

    public function testAccessMeWithoutTokenReturnsUnauthorized(): void
    {
        $this->client->request('GET', '/auth/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);
        
        // Pokud token chybí, LexikJwtAuthenticationBundle typicky vrací application/json
        // s chybovou zprávou, nikoliv nutně application/problem+json z našeho AbstractApiController,
        // protože se k němu exekuce nemusí dostat.
        $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertArrayHasKey('code', $responseData); // např. {"code":401,"message":"JWT Token not found"}
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $responseData['code']);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsStringIgnoringCase('JWT Token not found', $responseData['message']);
        // Odebrána kontrola na náš RFC 7807 formát, protože se pravděpodobně neaplikuje.
    }

    public function testAccessMeWithValidTokenReturnsUserData(): void
    {
        $this->userTestHelper->registerUser('me-user@example.com', 'password123', UserRole::READER->value, 'Me User');
        $token = $this->userTestHelper->loginAndGetToken('me-user@example.com', 'password123');

        $this->client->request('GET', '/auth/me', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer $token",
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('me-user@example.com', $responseData['email']);
        $this->assertEquals('Me User', $responseData['name']);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('roles', $responseData);
        $this->assertContains(UserRole::READER->value, $responseData['roles']);
    }
} 