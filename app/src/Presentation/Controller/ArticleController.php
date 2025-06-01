<?php

namespace App\Presentation\Controller;

use App\Application\Security\CurrentUserService;
use App\Domain\Article\Command\CreateArticleHandler;
use App\Domain\Article\Command\DeleteArticleHandler;
use App\Domain\Article\Command\UpdateArticleHandler;
use App\Domain\Article\DTO\CreateArticleRequest;
use App\Domain\Article\DTO\UpdateArticleRequest;
use App\Domain\Article\Entity\Article;
use App\Domain\Article\Repository\ArticleRepository;
use App\Shared\DTO\RequestDtoResolver;
use App\Shared\Security\AccessControlService;
use App\Shared\Validation\RequestDtoValidator;
use App\Shared\Exception\ValidationProblemException;
use App\Presentation\Controller\AbstractApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use RuntimeException;

#[Route('/articles')]
class ArticleController extends AbstractApiController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('', name: 'article_list', methods: ['GET'])]
    public function list(
        ArticleRepository $repository,
        CurrentUserService $currentUserService,
        AccessControlService $access
    ): JsonResponse
    {
        $user = $currentUserService->getUser();

        if (!$access->canViewList($user, Article::class)) {
            return $this->createForbiddenResponse();
        }

        try {
            $articles = $repository->findAll();
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An unexpected error occurred while fetching article list.');
        }

        $jsonArticles = $this->serializer->serialize($articles, 'json', ['groups' => 'article:list']);
        return new JsonResponse($jsonArticles, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'article_detail', methods: ['GET'])]
    public function detail(
        string $id,
        ArticleRepository $repository,
        CurrentUserService $currentUserService,
        AccessControlService $access
    ): JsonResponse
    {
        try {
            $article = $repository->findById($id);
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An unexpected error occurred while fetching article details.');
        }

        if (!$article) {
            return $this->createNotFoundResponse('Article not found');
        }

        $user = $currentUserService->getUser();

        if (!$access->canViewDetail($user, $article)) {
            return $this->createForbiddenResponse();
        }

        $jsonArticle = $this->serializer->serialize($article, 'json', ['groups' => 'article:read']);
        return new JsonResponse($jsonArticle, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'article_create', methods: ['POST'])]
    public function create(
        Request $request,
        CurrentUserService $currentUserService,
        RequestDtoResolver $dtoResolver,
        RequestDtoValidator $requestDtoValidator,
        CreateArticleHandler $handler,
        AccessControlService $access
    ): JsonResponse {
        $user = $currentUserService->getUser();

        if (!$access->canCreate($user, Article::class)) {
            return $this->createForbiddenResponse();
        }

        $dto = $dtoResolver->resolve(CreateArticleRequest::class, $request);

        try {
            $requestDtoValidator->validate($dto);
            $article = $handler->handle($dto, $user);
        } catch (ValidationProblemException $e) {
            return $this->createProblemJsonResponse('Validation Failed', $e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY, '/errors/validation-failed', ['invalid-params' => $this->formatValidationErrors($e->getValidationErrors())]);
        } catch (RuntimeException $e) {
            return $this->createProblemJsonResponse('Bad Request', $e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An unexpected error occurred during article creation.');
        }

        $jsonArticle = $this->serializer->serialize($article, 'json', ['groups' => 'article:read']);
        return new JsonResponse($jsonArticle, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'article_update', methods: ['PUT'])]
    public function update(
        string $id,
        Request $request,
        CurrentUserService $currentUserService,
        ArticleRepository $repository,
        AccessControlService $access,
        RequestDtoResolver $dtoResolver,
        RequestDtoValidator $requestDtoValidator,
        UpdateArticleHandler $handler,
    ): JsonResponse {
        $user = $currentUserService->getUser();

        try {
            $articleEntity = $repository->findById($id);
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An unexpected error occurred while fetching article for update.');
        }

        if (!$articleEntity) {
            return $this->createNotFoundResponse('Article not found');
        }

        if (!$access->canEdit($user, $articleEntity)) {
            return $this->createForbiddenResponse();
        }

        $dto = $dtoResolver->resolve(UpdateArticleRequest::class, $request);

        try {
            $requestDtoValidator->validate($dto);
            $updatedArticle = $handler->handle($articleEntity, $dto);
        } catch (ValidationProblemException $e) {
            return $this->createProblemJsonResponse('Validation Failed', $e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY, '/errors/validation-failed', ['invalid-params' => $this->formatValidationErrors($e->getValidationErrors())]);
        } catch (RuntimeException $e) {
            return $this->createProblemJsonResponse('Bad Request', $e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An unexpected error occurred during article update.');
        }

        $jsonArticle = $this->serializer->serialize($updatedArticle, 'json', ['groups' => 'article:read']);
        return new JsonResponse($jsonArticle, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'article_delete', methods: ['DELETE'])]
    public function delete(
        string $id,
        CurrentUserService $currentUserService,
        ArticleRepository $repository,
        AccessControlService $access,
        DeleteArticleHandler $handler,
    ): JsonResponse {
        $user = $currentUserService->getUser();

        try {
            $article = $repository->findById($id);
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An unexpected error occurred while fetching article for deletion.');
        }

        if (!$article) {
            return $this->createNotFoundResponse('Article not found');
        }

        if (!$access->canDelete($user, $article)) {
            return $this->createForbiddenResponse();
        }

        try {
            $handler->handle($article);
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An unexpected error occurred during article deletion.');
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
