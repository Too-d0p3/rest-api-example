<?php

namespace App\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApiController extends SymfonyAbstractController
{
    /**
     * Creates a JSON response according to RFC 7807 Problem Details for HTTP APIs.
     *
     * @param string $title A short, human-readable summary of the problem type.
     * @param string $detail A human-readable explanation specific to this occurrence of the problem.
     * @param int $status The HTTP status code for this occurrence of the problem.
     * @param string|null $type A URI reference that identifies the problem type. Defaults to 'about:blank'.
     * @param array $additionalDetails Additional members to include in the problem details object.
     * @return JsonResponse
     */
    protected function createProblemJsonResponse(
        string $title,
        string $detail,
        int $status,
        ?string $type = null,
        array $additionalDetails = []
    ): JsonResponse {
        $data = array_merge(
            $additionalDetails, // Allows adding custom fields like 'invalid-params'
            [
                'type' => $type ?? 'about:blank',
                'title' => $title,
                'status' => $status,
                'detail' => $detail,
            ]
        );

        return new JsonResponse($data, $status, ['Content-Type' => 'application/problem+json']);
    }

    /**
     * Helper to create a 404 Not Found problem response.
     */
    protected function createNotFoundResponse(string $detail = 'The requested resource was not found.'): JsonResponse
    {
        return $this->createProblemJsonResponse(
            'Not Found',
            $detail,
            Response::HTTP_NOT_FOUND,
            '/errors/not-found' // Example type URI
        );
    }

    /**
     * Helper to create a 403 Forbidden problem response.
     */
    protected function createForbiddenResponse(string $detail = 'You do not have permission to access this resource.'): JsonResponse
    {
        return $this->createProblemJsonResponse(
            'Forbidden',
            $detail,
            Response::HTTP_FORBIDDEN,
            '/errors/forbidden'
        );
    }
    
    /**
     * Helper to create a 500 Internal Server Error problem response.
     */
    protected function createInternalServerErrorResponse(string $detail = 'An unexpected error occurred.'): JsonResponse
    {
        return $this->createProblemJsonResponse(
            'Internal Server Error',
            $detail,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            '/errors/internal-server-error'
        );
    }

    /**
     * Formats validation errors for RFC 7807 invalid-params.
     * Example structure from DtoValidator: ['propertyName' => ['Error message 1', 'Error message 2']]
     * Output: [['name' => 'propertyName', 'reason' => 'Error message 1, Error message 2']]
     */
    protected function formatValidationErrors(array $errors): array
    {
        $formatted = [];
        foreach ($errors as $field => $fieldErrors) {
            // Zajistíme, že $fieldErrors je vždy pole, i když obsahuje jen jednu chybu (string)
            $errorMessages = [];
            if (is_array($fieldErrors)) {
                foreach ($fieldErrors as $error) {
                    if (is_string($error)) {
                        $errorMessages[] = $error;
                    }
                }
            } elseif (is_string($fieldErrors)) {
                $errorMessages[] = $fieldErrors;
            }

            if (!empty($errorMessages)) {
                 $formatted[] = [
                    'name' => $field,
                    'reason' => implode(', ', $errorMessages)
                ];
            }
        }
        return $formatted;
    }
} 