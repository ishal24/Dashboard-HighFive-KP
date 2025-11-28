<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        // central place for logging/observability
        $this->reportable(function (Throwable $e) {
            // Hook your logger/Sentry/etc here if needed.
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // 1) CSRF / 419 Page Expired → bounce back with old input (HTML) or 419 JSON (API)
        if ($e instanceof TokenMismatchException) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Page expired. Please retry.'], 419);
            }
            return redirect()->back()
                ->withInput() // repopulate non-sensitive fields
                ->with('status', 'Your session expired. Please try again.');
        }

        // 2) Validation: keep Laravel’s normal behavior (JSON vs Redirect)
        if ($e instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($e, $request);
        }

        // 3) Auth → for HTML redirect to login; for JSON return 401
        if ($e instanceof AuthenticationException) {
            return $this->unauthenticated($request, $e);
        }

        // 4) 404s: JSON vs HTML
        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Not Found'], 404);
            }
            // Optionally render a custom 404 view:
            // return response()->view('errors.404', [], 404);
        }

        // 5) Throttle → keep JSON clean
        if ($e instanceof TooManyRequestsHttpException) {
            if ($request->expectsJson()) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? null;
                return response()->json(
                    ['message' => 'Too Many Requests', 'retry_after' => $retryAfter],
                    429,
                    $retryAfter ? ['Retry-After' => $retryAfter] : []
                );
            }
        }

        // 6) For HttpExceptions keep code; for others fallback to parent (shows Whoops in local)
        if ($e instanceof HttpExceptionInterface && $request->expectsJson()) {
            $status = $e->getStatusCode();
            return response()->json(['message' => $e->getMessage() ?: 'Error'], $status, $e->getHeaders());
        }

        return parent::render($request, $e);
    }

    /**
     * Customize unauthenticated behavior.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        return redirect()->guest(route('login'));
    }
}
