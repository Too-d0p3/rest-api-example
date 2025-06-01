<?php

namespace App\Tests\Functional\User;

use App\Domain\User\Enum\UserRole;
use App\Tests\Functional\FunctionalTestCase;
use App\Tests\Helper\UserTestHelper;
use Symfony\Component\HttpFoundation\Response;

class UserEndpointTest extends FunctionalTestCase
{
    private UserTestHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new UserTestHelper($this->client);
    }

    public function testAdminCanCreateUser(): void
    {
        $this->helper->registerUser('admin@example.com', 'password', UserRole::ADMIN->value);
        $token = $this->helper->loginAndGetToken('admin@example.com', 'password');

        $this->client->request('POST', '/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer $token",
        ], json_encode([
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'password' => 'secret123',
            'role' => UserRole::AUTHOR->value,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals('newuser@example.com', $responseData['email']);
    }

    public function testNonAdminCannotCreateUser(): void
    {
        $this->helper->registerUser('author@example.com', 'password', UserRole::AUTHOR->value);
        $token = $this->helper->loginAndGetToken('author@example.com', 'password');

        $this->client->request('POST', '/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer $token",
        ], json_encode([
            'email' => 'someone@example.com',
            'name' => 'Someone',
            'password' => 'pass123',
            'role' => UserRole::READER->value,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);
        $this->assertEquals('application/problem+json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('Forbidden', $responseData['title']);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $responseData['status']);
    }

    public function testCreateUserDuplicateFails(): void
    {
        $this->helper->registerUser('admin@example.com', 'password', UserRole::ADMIN->value);
        $token = $this->helper->loginAndGetToken('admin@example.com', 'password');

        $this->client->request('POST', '/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer $token",
        ], json_encode([
            'email' => 'dup@example.com',
            'name' => 'Dup1',
            'password' => '123456',
            'role' => UserRole::AUTHOR->value,
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Try creating again
        $this->client->request('POST', '/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer $token",
        ], json_encode([
            'email' => 'dup@example.com',
            'name' => 'Dup2',
            'password' => 'abcdef',
            'role' => UserRole::AUTHOR->value,
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);
        $this->assertEquals('application/problem+json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('User already exists', $responseData['title']);
        $this->assertEquals(Response::HTTP_CONFLICT, $responseData['status']);
        $this->assertStringContainsString('dup@example.com', $responseData['detail']);
    }

    public function testAdminCanListAndSeeUserDetail(): void
    {
        $adminToken = $this->helper->registerAndLogin('admin@example.com', UserRole::ADMIN->value);
        $userIdToFind = $this->helper->registerUserAndGetId('user1@example.com', 'ValidPass123', UserRole::AUTHOR->value, 'User One Test', $adminToken);

        // List
        $this->client->request('GET', '/users', [], [], ['HTTP_AUTHORIZATION' => "Bearer $adminToken"]);
        $this->assertResponseIsSuccessful();
        $listContent = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('user1@example.com', $listContent);
        $listData = json_decode($listContent, true);
        $foundInList = false;
        foreach ($listData as $userInList) {
            if ($userInList['id'] === $userIdToFind) {
                $foundInList = true;
                break;
            }
        }
        $this->assertTrue($foundInList, 'Newly created user not found in list.');

        // Get detail
        $this->client->request('GET', "/users/$userIdToFind", [], [], ['HTTP_AUTHORIZATION' => "Bearer $adminToken"]);
        $this->assertResponseIsSuccessful();
        $detailData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('user1@example.com', $detailData['email']);
        $this->assertEquals($userIdToFind, $detailData['id']);
    }

    public function testNonAdminCannotListOrSeeUserDetail(): void
    {
        $readerToken = $this->helper->registerAndLogin('reader@example.com', UserRole::READER->value);
        $adminToken = $this->helper->registerAndLogin('admin-nonlist@example.com', UserRole::ADMIN->value);
        $userIdToFetch = $this->helper->registerUserAndGetId('anotheruser@example.com','ValidPass456', UserRole::AUTHOR->value, 'Another User Test', $adminToken);

        // Try to list
        $this->client->request('GET', '/users', [], [], ['HTTP_AUTHORIZATION' => "Bearer $readerToken"]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $responseContentList = $this->client->getResponse()->getContent();
        $responseDataList = json_decode($responseContentList, true);
        $this->assertEquals('application/problem+json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('Forbidden', $responseDataList['title']);

        // Try to see detail
        $this->client->request('GET', "/users/$userIdToFetch", [], [], ['HTTP_AUTHORIZATION' => "Bearer $readerToken"]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $responseContentDetail = $this->client->getResponse()->getContent();
        $responseDataDetail = json_decode($responseContentDetail, true);
        $this->assertEquals('application/problem+json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('Forbidden', $responseDataDetail['title']);
    }

    public function testAdminCanUpdateAndDeleteUser(): void
    {
        $adminToken = $this->helper->registerAndLogin('admin-update@example.com', UserRole::ADMIN->value);
        $userId = $this->helper->registerUserAndGetId('victim@example.com', 'ValidPass789', UserRole::AUTHOR->value, 'Victim User Test', $adminToken);

        // Update
        $this->client->request('PUT', "/users/$userId", [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer $adminToken",
        ], json_encode([
            'email' => 'victim-updated@example.com',
            'name' => 'Renamed Victim',
            'role' => UserRole::READER->value,
        ]));
        $this->assertResponseIsSuccessful();
        $updatedData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('victim-updated@example.com', $updatedData['email']);
        $this->assertEquals('Renamed Victim', $updatedData['name']);

        // Delete
        $this->client->request('DELETE', "/users/$userId", [], [], ['HTTP_AUTHORIZATION' => "Bearer $adminToken"]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT); // Očekáváme 204 No Content
    }

    public function testNonAdminCannotUpdateOrDeleteUser(): void
    {
        $authorToken = $this->helper->registerAndLogin('author-nonupdate@example.com', UserRole::AUTHOR->value);
        
        $adminToken = $this->helper->registerAndLogin('admin-owner-nonupdate@example.com', UserRole::ADMIN->value);
        $targetUserId = $this->helper->registerUserAndGetId('target@example.com', 'ValidPassABC', UserRole::AUTHOR->value, 'Target User Test', $adminToken);

        // Author tries to update another user (targetUser)
        $this->client->request('PUT', "/users/$targetUserId", [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer $authorToken",
        ], json_encode([
            'email' => 'target-hacked@example.com',
            'name' => 'Nope',
            'role' => UserRole::ADMIN->value,
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $responseContentUpdate = $this->client->getResponse()->getContent();
        $responseDataUpdate = json_decode($responseContentUpdate, true);
        $this->assertEquals('application/problem+json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('Forbidden', $responseDataUpdate['title']);

        // Author tries to delete another user (targetUser)
        $this->client->request('DELETE', "/users/$targetUserId", [], [], ['HTTP_AUTHORIZATION' => "Bearer $authorToken"]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $responseContentDelete = $this->client->getResponse()->getContent();
        $responseDataDelete = json_decode($responseContentDelete, true);
        $this->assertEquals('application/problem+json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('Forbidden', $responseDataDelete['title']);
    }
}
