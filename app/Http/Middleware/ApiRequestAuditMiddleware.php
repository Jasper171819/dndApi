<?php
// Developer context: This middleware attaches request timing and IDs to API traffic, then delegates all structured logging to ApiAuditLogger so the API log stays consistent.
// Clear explanation: This file tracks each API request and writes success, warning, or error logs without changing the API responses.

namespace App\Http\Middleware;

use App\Support\ApiAuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiRequestAuditMiddleware
{
    public function __construct(
        private readonly ApiAuditLogger $apiAuditLogger,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->apiAuditLogger->begin($request);

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->apiAuditLogger->logException($request, $exception);

            throw $exception;
        }

        $response->headers->set('X-Request-Id', $this->apiAuditLogger->requestId($request));
        $this->apiAuditLogger->logResponse($request, $response);

        return $response;
    }
}
