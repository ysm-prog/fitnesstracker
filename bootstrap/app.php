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
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

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

            return match (true) {
                $e instanceof ValidationException => ApiError::response(
                    ErrorCode::VALIDATION_FAILED,
                    'The submitted data is not valid.',
                    422,
                    $e->errors(),
                ),
                $e instanceof AuthenticationException => ApiError::response(
                    ErrorCode::UNAUTHENTICATED,
                    'Sign in to continue.',
                    401,
                ),
                $e instanceof AuthorizationException => ApiError::response(
                    ErrorCode::NOT_FOUND,
                    'The requested resource does not exist.',
                    404,
                ),
                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => ApiError::response(
                    ErrorCode::NOT_FOUND,
                    'The requested resource does not exist.',
                    404,
                ),
                $e instanceof MethodNotAllowedHttpException => ApiError::response(
                    ErrorCode::METHOD_NOT_ALLOWED,
                    'That method is not allowed for this endpoint.',
                    405,
                ),
                $e instanceof TooManyRequestsHttpException => ApiError::response(
                    ErrorCode::TOO_MANY_REQUESTS,
                    'Too many attempts. Try again shortly.',
                    429,
                ),
                $e instanceof HttpExceptionInterface && $e->getStatusCode() === 403 => ApiError::response(
                    ErrorCode::FORBIDDEN,
                    'You do not have access to that.',
                    403,
                ),
                default => ApiError::response(
                    ErrorCode::SERVER_ERROR,
                    'Something went wrong on our side. The correlation ID identifies this request in our logs.',
                    500,
                ),
            };
        });
    })->create();
