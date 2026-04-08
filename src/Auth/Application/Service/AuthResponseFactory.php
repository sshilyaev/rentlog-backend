<?php

namespace App\Auth\Application\Service;

use App\Auth\Domain\Entity\User;

final class AuthResponseFactory
{
    public function make(User $user, ?string $token = null, int $expiresIn = 3600): array
    {
        $response = [
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'roles' => $user->getRoles(),
            ],
        ];

        if ($token !== null) {
            $response['token'] = [
                'accessToken' => $token,
                'tokenType' => 'Bearer',
                'expiresIn' => $expiresIn,
            ];
        }

        return $response;
    }
}
