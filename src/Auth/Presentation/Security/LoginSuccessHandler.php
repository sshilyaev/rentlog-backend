<?php

namespace App\Auth\Presentation\Security;

use App\Auth\Application\Service\AuthResponseFactory;
use App\Auth\Application\Service\RefreshTokenIssuer;
use App\Auth\Domain\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly AuthResponseFactory $authResponseFactory,
        private readonly JWTTokenManagerInterface $jwtTokenManager,
        private readonly RefreshTokenIssuer $refreshTokenIssuer,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?JsonResponse
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'invalid_user_context',
                    'message' => 'Не удалось определить пользователя после входа.',
                ],
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $jwt = $this->jwtTokenManager->create($user);
        $refresh = $this->refreshTokenIssuer->issue($user);

        return new JsonResponse([
            'success' => true,
            'data' => $this->authResponseFactory->make($user, $jwt, $refresh),
        ]);
    }
}
