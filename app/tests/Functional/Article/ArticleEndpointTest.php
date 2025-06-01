<?php

namespace App\Tests\Functional\Article;

use App\Domain\User\Enum\UserRole;
use App\Tests\Functional\FunctionalTestCase;
use App\Tests\Helper\UserTestHelper;
use Symfony\Component\HttpFoundation\Response;

class ArticleEndpointTest extends FunctionalTestCase
{
    private UserTestHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new UserTestHelper($this->client);
    }

    public function testReaderCannotCreateArticle(): void
    {
        $this->helper->registerUser('reader@example.com', 'password', UserRole::READER->value);
        $token = $this->helper->loginAndGetToken('reader@example.com', 'password');

        $this->client->request('POST', '/articles', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer $token",
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test title',
            'content' => 'Text Content Content'
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);
        $this->assertEquals('application/problem+json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('Forbidden', $responseData['title']);
    }

    public function testAuthorCanCreateEditDeleteOwnArticle(): void
    {
        $this->helper->registerUser('author-article-test@example.com', 'password', UserRole::AUTHOR->value);
        $token = $this->helper->loginAndGetToken('author-article-test@example.com', 'password');

        // Create
        $this->client->request('POST', '/articles', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer $token",
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'A Title by Author',
            'content' => 'B Content Content by Author'
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $createResponseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $createResponseData);
        $this->assertEquals('A Title by Author', $createResponseData['title']);
        $id = $createResponseData['id'];

        // Edit
        $this->client->request('PUT', "/articles/$id", [], [], [
            'HTTP_AUTHORIZATION' => "Bearer $token",
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Updated by Author',
            'content' => 'Updated content by Author'
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $editResponseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated by Author', $editResponseData['title']);

        // Confirm update by GET
        $this->client->request('GET', "/articles/$id", [], [], ['HTTP_AUTHORIZATION' => "Bearer $token"]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $getResponseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated by Author', $getResponseData['title']);

        // Delete
        $this->client->request('DELETE', "/articles/$id", [], [], ['HTTP_AUTHORIZATION' => "Bearer $token"]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testOtherUserCannotEditOrDeleteForeignArticle(): void
    {
        // Owner creates an article
        $ownerToken = $this->helper->registerAndLogin('owner-article@example.com', UserRole::AUTHOR->value);
        $this->client->request('POST', '/articles', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer $ownerToken",
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Foreign Article',
            'content' => 'Content of Foreign Article'
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $articleId = json_decode($this->client->getResponse()->getContent(), true)['id'];

        // Another user (author) tries to manipulate it
        $otherToken = $this->helper->registerAndLogin('other-author@example.com', UserRole::AUTHOR->value);

        // Try to update
        $this->client->request('PUT', "/articles/$articleId", [], [], [
            'HTTP_AUTHORIZATION' => "Bearer $otherToken",
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Hacked Title',
            'content' => 'Hacked Content'
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $responseContentUpdate = $this->client->getResponse()->getContent();
        $responseDataUpdate = json_decode($responseContentUpdate, true);
        $this->assertEquals('application/problem+json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('Forbidden', $responseDataUpdate['title']);

        // Try to delete
        $this->client->request('DELETE', "/articles/$articleId", [], [], ['HTTP_AUTHORIZATION' => "Bearer $otherToken"]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $responseContentDelete = $this->client->getResponse()->getContent();
        $responseDataDelete = json_decode($responseContentDelete, true);
        $this->assertEquals('application/problem+json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('Forbidden', $responseDataDelete['title']);

        // Confirm content was not changed by GETting it with owner token
        $this->client->request('GET', "/articles/$articleId", [], [], ['HTTP_AUTHORIZATION' => "Bearer $ownerToken"]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Foreign Article', $data['title']); // Original title
    }

    public function testAdminCanEditAndDeleteAnyArticle(): void
    {
        // Author creates article
        $authorToken = $this->helper->registerAndLogin('author-for-admin-test@example.com', UserRole::AUTHOR->value);
        $this->client->request('POST', '/articles', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer $authorToken",
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Admin Target Article',
            'content' => 'Content by author, target for admin'
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $articleId = json_decode($this->client->getResponse()->getContent(), true)['id'];

        // Admin edits and deletes
        $adminToken = $this->helper->registerAndLogin('admin-article-test@example.com', UserRole::ADMIN->value);

        $this->client->request('PUT', "/articles/$articleId", [], [], [
            'HTTP_AUTHORIZATION' => "Bearer $adminToken",
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Admin Edited Article',
            'content' => 'Changed by admin successfully'
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $editData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Admin Edited Article', $editData['title']);

        $this->client->request('DELETE', "/articles/$articleId", [], [], ['HTTP_AUTHORIZATION' => "Bearer $adminToken"]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testListReturnsArticles(): void
    {
        $authorToken = $this->helper->registerAndLogin('list-article-author@example.com', UserRole::AUTHOR->value);

        // Create article 1
        $this->client->request('POST', '/articles', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer $authorToken",
        ], json_encode([
            'title' => 'First Article for List',
            'content' => 'Content A is long enough now.',
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $article1Id = json_decode($this->client->getResponse()->getContent(), true)['id'];

        // Create article 2
         $this->client->request('POST', '/articles', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer $authorToken",
        ], json_encode([
            'title' => 'Second Article for List',
            'content' => 'Content B is also sufficient.',
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $article2Id = json_decode($this->client->getResponse()->getContent(), true)['id'];

        // List articles (can be done by any authenticated user, e.g. the author)
        $this->client->request('GET', '/articles', [], [], ['HTTP_AUTHORIZATION' => "Bearer $authorToken"]);
        $this->assertResponseIsSuccessful();

        $listData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($listData);
        
        $titles = array_column($listData, 'title');
        $this->assertContains('First Article for List', $titles);
        $this->assertContains('Second Article for List', $titles);
    }

    public function testDetailReturnsCorrectArticle(): void
    {
        $authorToken = $this->helper->registerAndLogin('detail-article-author@example.com', UserRole::AUTHOR->value);

        // Create article
        $this->client->request('POST', '/articles', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer $authorToken",
        ], json_encode([
            'title' => 'Specific Detail Article',
            'content' => 'Unique Detail Content',
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $createdData = json_decode($this->client->getResponse()->getContent(), true);
        $articleId = $createdData['id'];

        // Fetch article by ID (can be done by any authenticated user, e.g. the author)
        $this->client->request('GET', "/articles/$articleId", [], [], ['HTTP_AUTHORIZATION' => "Bearer $authorToken"]);
        $this->assertResponseIsSuccessful();

        $detailData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Specific Detail Article', $detailData['title']);
        $this->assertEquals('Unique Detail Content', $detailData['content']);
        $this->assertEquals($articleId, $detailData['id']);
    }
    
    public function testGetNonExistentArticleReturnsNotFound(): void
    {
        $token = $this->helper->registerAndLogin('reader-notfound@example.com', UserRole::READER->value);
        $nonExistentId = '17db6550-9153-4533-9348-3340ef9889af'; // Random UUID

        $this->client->request('GET', "/articles/$nonExistentId", [], [], ['HTTP_AUTHORIZATION' => "Bearer $token"]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);
        $this->assertEquals('application/problem+json', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals('Not Found', $responseData['title']);
        $this->assertEquals('Article not found', $responseData['detail']);
    }
}
