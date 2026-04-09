<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class ProdWebProfilerGuardSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $kernelEnvironment,
        private readonly bool $webProfilerEnabled,
        private readonly string $profilerHttpPassword,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 512]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->kernelEnvironment !== 'prod') {
            return;
        }

        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        if (!str_starts_with($path, '/_profiler') && !str_starts_with($path, '/_wdt')) {
            return;
        }

        if (!$this->webProfilerEnabled) {
            throw new NotFoundHttpException();
        }

        if ($this->profilerHttpPassword === '') {
            throw new NotFoundHttpException();
        }
    }
}
