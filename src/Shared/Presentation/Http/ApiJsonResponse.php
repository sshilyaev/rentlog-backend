<?php

namespace App\Shared\Presentation\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiJsonResponse extends JsonResponse
{
    public static function success(array $data = [], int $status = self::HTTP_OK): self
    {
        return new self([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    public static function error(string $code, string $message, int $status): self
    {
        return new self([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
