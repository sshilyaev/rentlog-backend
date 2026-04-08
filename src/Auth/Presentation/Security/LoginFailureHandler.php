<?php

namespace App\Auth\Presentation\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'invalid_credentials',
                'message' => 'Неверный email или пароль.',
            ],
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }
}
