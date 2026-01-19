<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // Trata erros de validação
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Validation failed',
                'errors' => $exception->errors()
            ], 422);
        }

        // Trata erros 404
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Resource not found'
            ], 404);
        }

        // Trata erros de autorização
        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Trata erros HTTP
        if ($exception instanceof HttpException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $exception->getMessage() ?: 'HTTP Error',
                'status' => $exception->getStatusCode()
            ], $exception->getStatusCode());
        }

        // Erro genérico
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => env('APP_DEBUG') ? $exception->getMessage() : 'Internal server error'
        ], 500);
    }
}
