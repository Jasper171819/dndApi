<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiAuditLogger
{
    public const REQUEST_ID_ATTRIBUTE = 'api_request_id';

    public const STARTED_AT_ATTRIBUTE = 'api_started_at';

    public const EXCEPTION_LOGGED_ATTRIBUTE = 'api_exception_logged';

    public function begin(Request $request): void
    {
        if (! $request->attributes->has(self::REQUEST_ID_ATTRIBUTE)) {
            $request->attributes->set(self::REQUEST_ID_ATTRIBUTE, (string) Str::uuid());
        }

        if (! $request->attributes->has(self::STARTED_AT_ATTRIBUTE)) {
            $request->attributes->set(self::STARTED_AT_ATTRIBUTE, microtime(true));
        }
    }

    public function requestId(Request $request): string
    {
        $requestId = $request->attributes->get(self::REQUEST_ID_ATTRIBUTE);

        return is_string($requestId) && $requestId !== ''
            ? $requestId
            : (string) Str::uuid();
    }

    public function durationMs(Request $request): int
    {
        $startedAt = $request->attributes->get(self::STARTED_AT_ATTRIBUTE);

        if (! is_numeric($startedAt)) {
            return 0;
        }

        return max(0, (int) round((microtime(true) - (float) $startedAt) * 1000));
    }

    public function logResponse(Request $request, Response $response): void
    {
        if ($request->attributes->get(self::EXCEPTION_LOGGED_ATTRIBUTE) === true) {
            return;
        }

        $status = $response->getStatusCode();
        $payload = $this->responsePayload($response);
        $mutation = $status >= 200 && $status < 300 ? $this->mutationMetadata($request, $payload) : null;

        if ($mutation !== null) {
            $this->channel()->info('api.mutation', $this->mergeContext(
                $this->baseContext($request, $status),
                $mutation,
            ));

            return;
        }

        if ($status === 422) {
            $this->channel()->warning('api.warning', $this->mergeContext(
                $this->baseContext($request, $status),
                [
                    'warning_type' => 'validation_failed',
                    'error_fields' => $this->errorFieldsFromPayload($payload),
                    'message' => $this->truncate($payload['message'] ?? null),
                ],
            ));

            return;
        }

        if ($status === 404) {
            $this->channel()->warning('api.warning', $this->mergeContext(
                $this->baseContext($request, $status),
                [
                    'warning_type' => 'not_found',
                    'message' => $this->truncate($payload['message'] ?? null),
                ],
            ));

            return;
        }

        if ($status >= 500) {
            $this->channel()->error('api.error', $this->mergeContext(
                $this->baseContext($request, $status),
                [
                    'error_type' => 'server_error_response',
                    'message' => $this->truncate($payload['message'] ?? null),
                ],
            ));
        }
    }

    public function logException(Request $request, Throwable $exception): void
    {
        $request->attributes->set(self::EXCEPTION_LOGGED_ATTRIBUTE, true);
        $status = $this->statusFromThrowable($exception);

        if (in_array($status, [404, 422], true)) {
            $this->channel()->warning('api.warning', $this->mergeContext(
                $this->baseContext($request, $status),
                [
                    'warning_type' => $status === 422 ? 'validation_failed' : 'not_found',
                    'exception' => $exception::class,
                    'error_fields' => $exception instanceof ValidationException ? array_keys($exception->errors()) : null,
                    'message' => $this->truncate($exception->getMessage()),
                ],
            ));

            return;
        }

        $this->channel()->error('api.error', $this->mergeContext(
            $this->baseContext($request, $status),
            [
                'error_type' => 'unhandled_exception',
                'exception' => $exception::class,
                'message' => $this->truncate($exception->getMessage()),
            ],
        ));
    }

    private function baseContext(Request $request, ?int $status = null): array
    {
        return $this->mergeContext([
            'request_id' => $this->requestId($request),
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'route' => $request->route()?->uri(),
            'status' => $status,
            'duration_ms' => $this->durationMs($request),
            'route_parameters' => $this->routeParameters($request),
        ]);
    }

    private function mutationMetadata(Request $request, array $payload): ?array
    {
        $method = strtoupper($request->method());
        $path = '/'.ltrim($request->path(), '/');

        return match (true) {
            $method === 'POST' && $path === '/api/characters' => [
                'action' => 'create',
                'entity_type' => 'character',
                'entity_id' => $this->jsonPath($payload, ['data', 'id']),
            ],
            $method === 'PUT' && preg_match('#^/api/characters/\d+$#', $path) === 1 => [
                'action' => 'update',
                'entity_type' => 'character',
                'entity_id' => $this->jsonPath($payload, ['data', 'id']) ?? $this->routeParameterId($request, 'id'),
            ],
            $method === 'DELETE' && preg_match('#^/api/characters/\d+$#', $path) === 1 => [
                'action' => 'delete',
                'entity_type' => 'character',
                'entity_id' => $this->routeParameterId($request, 'id'),
            ],
            default => null,
        };
    }

    private function responsePayload(Response $response): array
    {
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);

            return is_array($data) ? $data : [];
        }

        $content = $response->getContent();

        if (! is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function routeParameters(Request $request): ?array
    {
        $route = $request->route();

        if ($route === null) {
            return null;
        }

        $parameters = [];

        foreach ($route->parameters() as $key => $value) {
            $scalar = $this->normalizeParameter($value);

            if ($scalar === null || $scalar === '') {
                continue;
            }

            $parameters[$key] = $scalar;
        }

        return $parameters === [] ? null : $parameters;
    }

    private function routeParameterId(Request $request, string ...$keys): int|string|null
    {
        foreach ($keys as $key) {
            $value = $request->route($key);
            $normalized = $this->normalizeParameter($value);

            if ($normalized !== null && $normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeParameter(mixed $value): int|string|null
    {
        if ($value instanceof Model) {
            return $value->getKey();
        }

        if (is_scalar($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, 'getKey')) {
            return $value->getKey();
        }

        return null;
    }

    private function errorFieldsFromPayload(array $payload): ?array
    {
        $errors = $payload['errors'] ?? null;

        if (! is_array($errors)) {
            return null;
        }

        $keys = array_values(array_filter(array_keys($errors), static fn (mixed $key): bool => is_string($key) && $key !== ''));

        return $keys === [] ? null : $keys;
    }

    private function statusFromThrowable(Throwable $exception): int
    {
        if ($exception instanceof ValidationException) {
            return 422;
        }

        if ($exception instanceof NotFoundHttpException) {
            return 404;
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if (method_exists($exception, 'getStatusCode')) {
            $status = $exception->getStatusCode();

            return is_int($status) ? $status : 500;
        }

        return 500;
    }

    private function jsonPath(array $payload, array $segments): mixed
    {
        $current = $payload;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    private function truncate(mixed $value, int $limit = 160): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Str::limit(trim($value), $limit);
    }

    private function mergeContext(array ...$chunks): array
    {
        $merged = [];

        foreach ($chunks as $chunk) {
            foreach ($chunk as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    private function channel()
    {
        return Log::channel('api');
    }
}
