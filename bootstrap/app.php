<?php

use App\Http\Middleware\ApiRequestAuditMiddleware;
use App\Support\ApiAuditLogger;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [
            ApiRequestAuditMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $exception) {
            /** @var Request|null $request */
            $request = app()->bound('request') ? app('request') : null;

            if (! $request instanceof Request) {
                return;
            }

            if ($request->attributes->get(ApiAuditLogger::EXCEPTION_LOGGED_ATTRIBUTE) === true) {
                return;
            }

            app(ApiAuditLogger::class)->begin($request);
            app(ApiAuditLogger::class)->logException($request, $exception);
        });

        $exceptions->respond(function (Response $response) {
            /** @var Request|null $request */
            $request = app()->bound('request') ? app('request') : null;

            if ($request instanceof Request && $request->attributes->has(ApiAuditLogger::REQUEST_ID_ATTRIBUTE)) {
                $response->headers->set('X-Request-Id', (string) $request->attributes->get(ApiAuditLogger::REQUEST_ID_ATTRIBUTE));
            }

            return $response;
        });
    })->create();
