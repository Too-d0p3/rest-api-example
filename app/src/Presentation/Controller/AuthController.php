<?php
namespace App\Presentation\Controller;

use App\Application\Security\CurrentUserService;
use App\Domain\User\Command\LoginUserHandler;
use App\Domain\User\Command\RegisterUserHandler;
use App\Domain\User\Command\ChangePasswordHandler;
use App\Domain\User\DTO\ChangePasswordRequest;
use App\Domain\User\DTO\LoginUserRequest;
use App\Domain\User\DTO\RegisterUserRequest;
use App\Domain\User\Exception\InvalidOldPasswordException;
use App\Domain\User\Exception\UserAlreadyExistsException;
use App\Shared\Validation\RequestDtoValidator;
use App\Shared\Exception\ValidationProblemException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Shared\DTO\RequestDtoResolver;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/auth')]
class AuthController extends AbstractApiController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        RequestDtoValidator $requestDtoValidator,
        RegisterUserHandler $handler,
        RequestDtoResolver $dtoResolver,
    ): JsonResponse {
        /** @var RegisterUserRequest $dto */
        $dto = $dtoResolver->resolve(RegisterUserRequest::class, $request);

        try {
            $requestDtoValidator->validate($dto);
            $user = $handler->handle($dto);
            $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);
            return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
        } catch (ValidationProblemException $e) {
            return $this->createProblemJsonResponse(
                'Validation Failed',
                $e->getMessage(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                '/errors/validation-failed',
                ['invalid-params' => $this->formatValidationErrors($e->getValidationErrors())]
            );
        } catch (UserAlreadyExistsException $e) {
            return $this->createProblemJsonResponse(
                'User already exists',
                $e->getMessage(),
                Response::HTTP_CONFLICT,
                '/errors/user-already-exists'
            );
        } catch (RuntimeException $e) {
            return $this->createProblemJsonResponse(
                'Bad Request',
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse($e->getMessage());
        }
    }

    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(
        Request $request,
        RequestDtoValidator $requestDtoValidator,
        RequestDtoResolver $resolver,
        LoginUserHandler $handler,
    ): JsonResponse {
        /** @var LoginUserRequest $dto */
        $dto = $resolver->resolve(LoginUserRequest::class, $request);

        try {
            $requestDtoValidator->validate($dto);
            $token = $handler->handle($dto);
            return $this->json(['token' => $token]);
        } catch (ValidationProblemException $e) {
            return $this->createProblemJsonResponse(
                'Validation Failed',
                $e->getMessage(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                '/errors/validation-failed',
                ['invalid-params' => $this->formatValidationErrors($e->getValidationErrors())]
            );
        } catch (AuthenticationException $e) {
            return $this->createProblemJsonResponse(
                'Authentication Failed',
                $e->getMessage(),
                Response::HTTP_UNAUTHORIZED,
                '/errors/authentication-failed'
            );
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse($e->getMessage());
        }
    }

    #[Route('/me', name: 'auth_me', methods: ['GET'])]
    public function me(CurrentUserService $currentUserService): JsonResponse
    {
        try {
            $user = $currentUserService->getUser();
            if ($user === null) {
                return $this->createProblemJsonResponse('Not Authenticated', 'User not authenticated.', Response::HTTP_UNAUTHORIZED, '/errors/not-authenticated');
            }
            $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);
            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An error occurred while fetching user data.');
        }
    }

    #[Route('/change-password', name: 'auth_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        CurrentUserService $currentUserService,
        RequestDtoResolver $dtoResolver,
        RequestDtoValidator $validator,
        ChangePasswordHandler $handler,
    ): JsonResponse {
        /** @var ChangePasswordRequest $dto */
        $dto = $dtoResolver->resolve(ChangePasswordRequest::class, $request);

        try {
            $validator->validate($dto);
            $user = $currentUserService->getUser();
            $handler->handle($user, $dto);
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (ValidationProblemException $e) {
            return $this->createProblemJsonResponse(
                'Validation Failed',
                $e->getMessage(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                '/errors/validation-failed',
                ['invalid-params' => $this->formatValidationErrors($e->getValidationErrors())]
            );
        } catch (InvalidOldPasswordException $e) {
            return $this->createProblemJsonResponse(
                'Invalid Password',
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST,
                '/errors/invalid-password'
            );
        } catch (\Throwable $e) {
            return $this->createInternalServerErrorResponse('An error occurred while changing password.');
        }
    }
}