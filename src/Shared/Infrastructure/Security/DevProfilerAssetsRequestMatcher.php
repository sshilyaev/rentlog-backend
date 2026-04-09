<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

final class DevProfilerAssetsRequestMatcher implements RequestMatcherInterface
{
    public function __construct(
        private readonly string $kernelEnvironment,
    ) {
    }

    public function matches(Request $request): bool
    {
        if ($this->kernelEnvironment !== 'dev') {
            return false;
        }

        return (bool) preg_match('#^/(_(profiler|wdt)|css|images|js)/#', $request->getPathInfo());
    }
}
