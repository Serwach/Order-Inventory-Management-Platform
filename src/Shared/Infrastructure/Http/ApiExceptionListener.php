<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Domain\Exception\ConflictException;
use App\Shared\Domain\Exception\NotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $env,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        $request   = $event->getRequest();
        $exception = $event->getThrowable();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        [$statusCode, $title] = $this->resolveStatus($exception);

        $correlationId = $request->attributes->get('correlation_id', '');

        if ($statusCode >= 500) {
            $this->logger->error('Unhandled exception', [
                'exception'      => $exception::class,
                'message'        => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ]);
        }

        $body = [
            'status'         => $statusCode,
            'title'          => $title,
            'detail'         => $exception->getMessage(),
            'correlation_id' => $correlationId,
        ];

        if ($this->env !== 'prod' && $statusCode >= 500) {
            $body['trace'] = $exception->getTraceAsString();
        }

        $event->setResponse(new JsonResponse($body, $statusCode, [
            'Content-Type' => 'application/problem+json',
        ]));
    }

    /** @return array{int, string} */
    private function resolveStatus(\Throwable $e): array
    {
        if ($e instanceof HttpExceptionInterface) {
            return [$e->getStatusCode(), Response::$statusTexts[$e->getStatusCode()] ?? 'Error'];
        }

        if ($e instanceof NotFoundException) {
            return [Response::HTTP_NOT_FOUND, 'Not Found'];
        }

        if ($e instanceof ConflictException) {
            return [Response::HTTP_CONFLICT, 'Conflict'];
        }

        if ($e instanceof ValidationFailedException || $e instanceof \InvalidArgumentException) {
            return [Response::HTTP_UNPROCESSABLE_ENTITY, 'Validation Error'];
        }

        return [Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal Server Error'];
    }
}
