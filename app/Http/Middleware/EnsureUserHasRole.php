<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || (! empty($roles) && ! in_array($user->role, $roles, true))) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'You are not authorized to perform this action.',
                ], 403);
            }

            if ($request->is('admin') || $request->is('admin/*')) {
                return redirect()->route('admin.login')
                    ->with('error', 'Admin access required.');
            }

            return redirect()->route('organizer.login')
                ->with('error', 'Please sign in with an organizer account.');
        }

        return $next($request);
    }
}
