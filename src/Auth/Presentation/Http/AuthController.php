<?php

declare(strict_types=1);

namespace App\Auth\Presentation\Http;

use App\Auth\Application\Dto\ForgotPasswordRequestDto;
use App\Auth\Application\Dto\RefreshTokenRequestDto;
use App\Auth\Application\Dto\RegisterRequestDto;
use App\Auth\Application\Dto\ResetPasswordRequestDto;
use App\Auth\Application\Service\AuthResponseFactory;
use App\Auth\Application\Service\EmailVerificationService;
use App\Auth\Application\Service\PasswordResetService;
use App\Auth\Application\Service\RefreshTokenConsumer;
use App\Auth\Application\Service\RefreshTokenIssuer;
use App\Auth\Application\Service\RegisterUserService;
use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Exception\UserAlreadyExistsException;
use App\Property\Application\Service\LinkPropertyMembersByEmailService;
use App\Shared\Presentation\Http\ApiJsonResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/auth', name: 'api_v1_auth_')]
final class AuthController
{
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('Обработка входа выполняется через json_login firewall.');
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        RegisterUserService $registerUserService,
        JWTTokenManagerInterface $jwtTokenManager,
        AuthResponseFactory $authResponseFactory,
        LinkPropertyMembersByEmailService $linkPropertyMembersByEmailService,
        RefreshTokenIssuer $refreshTokenIssuer,
        TranslatorInterface $translator,
    ): Response {
        try {
            $dto = $serializer->deserialize($request->getContent(), RegisterRequestDto::class, 'json');
        } catch (SerializerExceptionInterface) {
            return ApiJsonResponse::errorTrans($translator, 'error.invalid_payload', Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return ApiJsonResponse::errorTrans($translator, 'error.validation_failed', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $user = $registerUserService->handle($dto);
        } catch (UserAlreadyExistsException) {
            return ApiJsonResponse::errorTrans($translator, 'error.user_already_exists', Response::HTTP_CONFLICT);
        }

        $linkedMembersCount = $linkPropertyMembersByEmailService->handle($user);
        $token = $jwtTokenManager->create($user);
        $refresh = $refreshTokenIssuer->issue($user);

        return ApiJsonResponse::success([
            'message' => $translator->trans('msg.auth.registered', [], 'api'),
            'linkedPropertyMembersCount' => $linkedMembersCount,
            ...$authResponseFactory->make($user, $token, $refresh),
        ], Response::HTTP_CREATED);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        RefreshTokenConsumer $refreshTokenConsumer,
        RefreshTokenIssuer $refreshTokenIssuer,
        JWTTokenManagerInterface $jwtTokenManager,
        AuthResponseFactory $authResponseFactory,
        TranslatorInterface $translator,
    ): Response {
        try {
            $dto = $serializer->deserialize($request->getContent(), RefreshTokenRequestDto::class, 'json');
        } catch (SerializerExceptionInterface) {
            return ApiJsonResponse::errorTrans($translator, 'error.invalid_payload', Response::HTTP_BAD_REQUEST);
        }

        if (count($validator->validate($dto)) > 0) {
            return ApiJsonResponse::errorTrans($translator, 'error.validation_failed', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $refreshTokenConsumer->consume($dto->refreshToken);
        if ($user === null) {
            return ApiJsonResponse::errorTrans($translator, 'error.auth.invalid_refresh', Response::HTTP_UNAUTHORIZED);
        }

        $access = $jwtTokenManager->create($user);
        $refresh = $refreshTokenIssuer->issue($user);

        return ApiJsonResponse::success($authResponseFactory->make($user, $access, $refresh));
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(
        Security $security,
        RefreshTokenIssuer $refreshTokenIssuer,
        TranslatorInterface $translator,
    ): Response {
        $user = $security->getUser();
        if (!$user instanceof User) {
            return ApiJsonResponse::errorTrans($translator, 'error.unauthenticated', Response::HTTP_UNAUTHORIZED);
        }

        $refreshTokenIssuer->revokeAllForUser($user);

        return ApiJsonResponse::success(['message' => $translator->trans('msg.auth.logged_out', [], 'api')]);
    }

    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PasswordResetService $passwordResetService,
        TranslatorInterface $translator,
    ): Response {
        try {
            $dto = $serializer->deserialize($request->getContent(), ForgotPasswordRequestDto::class, 'json');
        } catch (SerializerExceptionInterface) {
            return ApiJsonResponse::errorTrans($translator, 'error.invalid_payload', Response::HTTP_BAD_REQUEST);
        }

        if (count($validator->validate($dto)) > 0) {
            return ApiJsonResponse::errorTrans($translator, 'error.validation_failed', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $passwordResetService->requestReset($dto->email);

        return ApiJsonResponse::success([
            'message' => $translator->trans('msg.auth.password_reset_sent', [], 'api'),
        ]);
    }

    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PasswordResetService $passwordResetService,
        TranslatorInterface $translator,
    ): Response {
        try {
            $dto = $serializer->deserialize($request->getContent(), ResetPasswordRequestDto::class, 'json');
        } catch (SerializerExceptionInterface) {
            return ApiJsonResponse::errorTrans($translator, 'error.invalid_payload', Response::HTTP_BAD_REQUEST);
        }

        if (count($validator->validate($dto)) > 0) {
            return ApiJsonResponse::errorTrans($translator, 'error.validation_failed', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $passwordResetService->resetPassword($dto->token, $dto->password);
        if ($user === null) {
            return ApiJsonResponse::errorTrans($translator, 'error.auth.reset_invalid', Response::HTTP_BAD_REQUEST);
        }

        return ApiJsonResponse::success([
            'message' => $translator->trans('msg.auth.password_changed', [], 'api'),
        ]);
    }

    #[Route('/verify-email', name: 'verify_email', methods: ['GET'])]
    public function verifyEmail(
        Request $request,
        EmailVerificationService $emailVerificationService,
        TranslatorInterface $translator,
    ): Response {
        $token = (string) $request->query->get('token', '');
        if ($token === '') {
            return ApiJsonResponse::errorTrans($translator, 'error.auth.verify_token_missing', Response::HTTP_BAD_REQUEST);
        }

        $user = $emailVerificationService->verifyByToken($token);
        if ($user === null) {
            return ApiJsonResponse::errorTrans($translator, 'error.auth.verify_invalid', Response::HTTP_BAD_REQUEST);
        }

        return ApiJsonResponse::success([
            'message' => $translator->trans('msg.auth.email_verified', [], 'api'),
        ]);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(Security $security, TranslatorInterface $translator): Response
    {
        $user = $security->getUser();

        if ($user === null) {
            return ApiJsonResponse::errorTrans($translator, 'error.unauthenticated', Response::HTTP_UNAUTHORIZED);
        }

        if (!$user instanceof User) {
            return ApiJsonResponse::errorTrans($translator, 'error.invalid_user_context', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return ApiJsonResponse::success([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'emailVerified' => $user->isEmailVerified(),
            ],
        ]);
    }
}
