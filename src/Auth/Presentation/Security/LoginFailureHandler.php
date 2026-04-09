<?php

declare(strict_types=1);

namespace App\Auth\Presentation\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'error.invalid_credentials',
                'message' => $this->translator->trans('error.invalid_credentials', [], 'api'),
            ],
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }
}
