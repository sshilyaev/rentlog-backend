<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

final class ProdWebProfilerToolsRequestMatcher implements RequestMatcherInterface
{
    public function __construct(
        private readonly string $kernelEnvironment,
    ) {
    }

    public function matches(Request $request): bool
    {
        if ($this->kernelEnvironment !== 'prod') {
            return false;
        }

        $path = $request->getPathInfo();

        return str_starts_with($path, '/_profiler') || str_starts_with($path, '/_wdt');
    }
}
