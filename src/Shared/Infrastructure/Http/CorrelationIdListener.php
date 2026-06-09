<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Uid\Uuid;

final class CorrelationIdListener
{
    private const HEADER = 'X-Correlation-ID';

    private string $correlationId = '';

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->correlationId = $event->getRequest()->headers->get(self::HEADER)
            ?? Uuid::v7()->toRfc4122();

        $event->getRequest()->attributes->set('correlation_id', $this->correlationId);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || $this->correlationId === '') {
            return;
        }

        $event->getResponse()->headers->set(self::HEADER, $this->correlationId);
    }
}
