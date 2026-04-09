<?php

namespace App\Auth\Application\Service;

use App\Auth\Domain\Entity\User;

final class AuthResponseFactory
{
    private readonly int $jwtTtlSeconds;

    public function __construct(int|string $jwtTtlSeconds)
    {
        $this->jwtTtlSeconds = (int) $jwtTtlSeconds;
    }

    public function make(User $user, ?string $accessToken = null, ?string $refreshToken = null): array
    {
        $response = [
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'emailVerified' => $user->isEmailVerified(),
            ],
        ];

        if ($accessToken !== null) {
            $response['token'] = [
                'accessToken' => $accessToken,
                'refreshToken' => $refreshToken,
                'tokenType' => 'Bearer',
                'expiresIn' => $this->jwtTtlSeconds,
            ];
        }

        return $response;
    }
}
