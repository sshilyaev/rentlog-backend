<?php

namespace App\Health\Presentation\Http;

use App\Shared\Presentation\Http\ApiJsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/health', name: 'api_v1_health_', methods: ['GET'])]
final class HealthController
{
    #[Route('', name: 'show')]
    public function __invoke(): Response
    {
        return ApiJsonResponse::success([
            'status' => 'ok',
            'service' => 'rentlog-backend',
            'version' => 'v1',
        ]);
    }
}
