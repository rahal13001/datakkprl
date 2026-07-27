<?php

use App\Http\Middleware\AttachRequestId;
use App\Http\Middleware\EnsureMobileUserIsActive;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'mobile.active' => EnsureMobileUserIsActive::class,
            'request.id' => AttachRequestId::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/mobile/*')) {
                return null;
            }

            return response()->json([
                'message' => 'The submitted data is invalid.',
                'code' => 'validation_failed',
                'request_id' => $request->attributes->get('request_id'),
                'errors' => $exception->errors(),
            ], $exception->status);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->is('api/mobile/*')) {
                return null;
            }

            return response()->json([
                'message' => 'You are not authorized to perform this action.',
                'code' => 'forbidden',
                'request_id' => $request->attributes->get('request_id'),
            ], 403);
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            if (! $request->is('api/mobile/*')) {
                return null;
            }

            return response()->json([
                'message' => 'You are not authorized to perform this action.',
                'code' => 'forbidden',
                'request_id' => $request->attributes->get('request_id'),
            ], 403);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/mobile/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Authentication is required.',
                'code' => 'unauthenticated',
                'request_id' => $request->attributes->get('request_id'),
            ], 401);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/mobile/*')) {
                return null;
            }

            $status = $exception->getStatusCode();
            $codes = [
                404 => 'not_found',
                405 => 'method_not_allowed',
                409 => 'conflict',
                422 => 'unprocessable',
                429 => 'rate_limited',
            ];
            if (! isset($codes[$status])) {
                return null;
            }

            return response()->json([
                'message' => $status === 404
                    ? 'The requested resource was not found.'
                    : ($exception->getMessage() ?: 'The request could not be completed.'),
                'code' => $codes[$status],
                'request_id' => $request->attributes->get('request_id'),
            ], $status);
        });
    })->create();
