<?php

use App\Http\Middleware\EnsureUserHasRole;
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
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'organizer.approved' => \App\Http\Middleware\EnsureApprovedOrganizer::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'otp/send',
            'otp/verify',
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

            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.dashboard');
            }

            if ($user?->isCustomer()) {
                return route('tickets.index');
            }

            return route('organizer.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
