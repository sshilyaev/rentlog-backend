<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    /** @param non-empty-string $messageKey Key in domain `api` (e.g. error.invalid_payload) */
    public static function errorTrans(TranslatorInterface $translator, string $messageKey, int $status): self
    {
        return new self([
            'success' => false,
            'error' => [
                'code' => $messageKey,
                'message' => $translator->trans($messageKey, [], 'api'),
            ],
        ], $status);
    }
}
