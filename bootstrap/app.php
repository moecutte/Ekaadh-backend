<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'role' => EnsureUserHasRole::class,
            'organizer.approved' => \App\Http\Middleware\EnsureApprovedOrganizer::class,
        ]);

        $middleware->throttleApi('api');
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            if ($request->is('organizer') || $request->is('organizer/*')) {
                return route('organizer.login');
            }

            return route('customer.login');
        });
        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();

            if ($user) {
                return route($user->homeRoute());
            }

            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.dashboard');
            }

            if ($request->is('organizer') || $request->is('organizer/*')) {
                return route('organizer.dashboard');
            }

            return route('tickets.index');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Web OTP/checkout fetch() sends Accept: application/json. If we only
        // treat /api/* as JSON, ValidationException becomes a 302 HTML redirect;
        // fetch follows it and the UI shows "Could not send code (200)".
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Never leak stack traces / SQL to API clients (even when APP_DEBUG=true).
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Illuminate\Auth\Access\AuthorizationException
                || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return null;
            }

            report($e);

            return response()->json([
                'message' => 'Something went wrong. Please try again later.',
            ], 500);
        });
    })->create();
