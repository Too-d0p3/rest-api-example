<?php

namespace App\Presentation\Controller;

use App\Application\Security\CurrentUserService;
use App\Domain\User\Command\CreateUserHandler;
use App\Domain\User\Command\DeleteUserHandler;
use App\Domain\User\Command\UpdateUserHandler;
use App\Domain\User\DTO\CreateUserRequest;
use App\Domain\User\DTO\UpdateUserRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Exception\UserAlreadyExistsException;
use App\Domain\User\Repository\UserRepository;
use App\Shared\DTO\RequestDtoResolver;
use App\Shared\Security\AccessControlService;
use App\Shared\Validation\RequestDtoValidator;
use App\Shared\Exception\ValidationProblemException;
use RuntimeException;
use App\Presentation\Controller\AbstractApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/users')]
class UserController extends AbstractApiController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('', name: 'user_list', methods: ['GET'])]
    public function list(
        CurrentUserService $currentUserService,
        AccessControlService $access,
        UserRepository $repository
    ): JsonResponse {
        $admin = $currentUserService->getUser();

        if (!$access->canViewList($admin, User::class)) {
            return $this->createForbiddenResponse();
        }

        try {
            $users = $repository->findAll();
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An unexpected error occurred while fetching user list.');
        }

        $jsonUsers = $this->serializer->serialize($users, 'json', ['groups' => 'user:list']);
        return new JsonResponse($jsonUsers, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'user_detail', methods: ['GET'])]
    public function detail(
        string $id,
        CurrentUserService $currentUserService,
        AccessControlService $access,
        UserRepository $repository
    ): JsonResponse {
        $admin = $currentUserService->getUser();
        
        try {
            $user = $repository->findById($id);
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An unexpected error occurred while fetching user details.');
        }

        if (!$user) {
            return $this->createNotFoundResponse('User not found');
        }

        if (!$access->canViewDetail($admin, $user)) {
            return $this->createForbiddenResponse();
        }

        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'user_create', methods: ['POST'])]
    public function create(
        Request $request,
        CurrentUserService $currentUserService,
        AccessControlService $access,
        RequestDtoResolver $dtoResolver,
        RequestDtoValidator $requestDtoValidator,
        CreateUserHandler $handler
    ): JsonResponse {
        $admin = $currentUserService->getUser();

        if (!$access->canCreate($admin, User::class)) {
            return $this->createForbiddenResponse();
        }

        $dto = $dtoResolver->resolve(CreateUserRequest::class, $request);

        try {
            $requestDtoValidator->validate($dto);
            $user = $handler->handle($dto);
        } catch (ValidationProblemException $e) {
            return $this->createProblemJsonResponse('Validation Failed', $e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY, '/errors/validation-failed', ['invalid-params' => $this->formatValidationErrors($e->getValidationErrors())]);
        } catch (UserAlreadyExistsException $e) {
            return $this->createProblemJsonResponse('User already exists', $e->getMessage(), Response::HTTP_CONFLICT, '/errors/user-already-exists');
        } catch (RuntimeException $e) {
            return $this->createProblemJsonResponse('Bad Request', $e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An unexpected error occurred during user creation.');
        }

        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'user_update', methods: ['PUT'])]
    public function update(
        string $id,
        Request $request,
        CurrentUserService $currentUserService,
        AccessControlService $access,
        UserRepository $repository,
        RequestDtoResolver $dtoResolver,
        RequestDtoValidator $requestDtoValidator,
        UpdateUserHandler $handler
    ): JsonResponse {
        $admin = $currentUserService->getUser();

        try {
            $userEntity = $repository->findById($id);
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An unexpected error occurred while fetching user for update.');
        }

        if (!$userEntity) {
            return $this->createNotFoundResponse('User not found');
        }

        if (!$access->canEdit($admin, $userEntity)) {
            return $this->createForbiddenResponse();
        }

        $dto = $dtoResolver->resolve(UpdateUserRequest::class, $request);

        try {
            $requestDtoValidator->validate($dto);
            $updatedUser = $handler->handle($userEntity, $dto);
        } catch (ValidationProblemException $e) {
            return $this->createProblemJsonResponse('Validation Failed', $e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY, '/errors/validation-failed', ['invalid-params' => $this->formatValidationErrors($e->getValidationErrors())]);
        } catch (UserAlreadyExistsException $e) {
            return $this->createProblemJsonResponse('User already exists', $e->getMessage(), Response::HTTP_CONFLICT, '/errors/user-already-exists');
        } catch (RuntimeException $e) {
            return $this->createProblemJsonResponse('Bad Request', $e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An unexpected error occurred during user update.');
        }

        $jsonUser = $this->serializer->serialize($updatedUser, 'json', ['groups' => 'user:read']);
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(
        string $id,
        CurrentUserService $currentUserService,
        AccessControlService $access,
        UserRepository $repository,
        DeleteUserHandler $handler
    ): JsonResponse {
        $admin = $currentUserService->getUser();

        try {
            $user = $repository->findById($id);
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An unexpected error occurred while fetching user for deletion.');
        }

        if (!$user) {
            return $this->createNotFoundResponse('User not found');
        }

        if (!$access->canDelete($admin, $user)) {
            return $this->createForbiddenResponse();
        }

        try {
            $handler->handle($user);
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An unexpected error occurred during user deletion.');
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
