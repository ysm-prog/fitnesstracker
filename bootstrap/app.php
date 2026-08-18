<?php

use App\Http\Middleware\AssignCorrelationId;
use App\Support\ApiError;
use App\Support\ErrorCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global, not group-scoped: a request that matches no route never
        // reaches group middleware, and a 404 needs a correlation ID too.
        $middleware->prepend(AssignCorrelationId::class);

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return ApiError::response(
                    ErrorCode::VALIDATION_FAILED,
                    'The submitted data is not valid.',
                    422,
                    $e->errors(),
                );
            }

            if ($e instanceof AuthenticationException) {
                return ApiError::fromStatus(401);
            }

            // A policy may deny as not found, which Laravel turns into a plain
            // HttpException carrying only a status. Read the status rather than
            // matching on exception classes that will not all be present.
            if ($e instanceof AuthorizationException) {
                return ApiError::fromStatus($e->hasStatus() ? $e->status() : 403, $e->response()->message());
            }

            if ($e instanceof ModelNotFoundException) {
                return ApiError::fromStatus(404);
            }

            if ($e instanceof HttpExceptionInterface) {
                return ApiError::fromStatus($e->getStatusCode());
            }

            return ApiError::fromStatus(500);
        });

    })->create();
