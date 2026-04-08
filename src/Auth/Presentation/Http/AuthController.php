<?php

namespace App\Auth\Presentation\Http;

use App\Auth\Application\Dto\RegisterRequestDto;
use App\Auth\Application\Service\RegisterUserService;
use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Exception\UserAlreadyExistsException;
use App\Shared\Presentation\Http\ApiJsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/auth', name: 'api_v1_auth_')]
final class AuthController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        RegisterUserService $registerUserService
    ): Response
    {
        /** @var RegisterRequestDto $dto */
        $dto = $serializer->deserialize($request->getContent(), RegisterRequestDto::class, 'json');
        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return ApiJsonResponse::error(
                'validation_failed',
                'Некорректные данные для регистрации.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $user = $registerUserService->handle($dto);
        } catch (UserAlreadyExistsException $exception) {
            return ApiJsonResponse::error(
                'user_already_exists',
                $exception->getMessage(),
                Response::HTTP_CONFLICT
            );
        }

        return ApiJsonResponse::success([
            'message' => 'Пользователь зарегистрирован.',
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(Security $security): Response
    {
        $user = $security->getUser();

        if ($user === null) {
            return ApiJsonResponse::error(
                'unauthenticated',
                'Пользователь не аутентифицирован.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        if (!$user instanceof User) {
            return ApiJsonResponse::error(
                'invalid_user_context',
                'Текущий пользователь имеет неподдерживаемый тип.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return ApiJsonResponse::success([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'roles' => $user->getRoles(),
        ]);
    }
}
