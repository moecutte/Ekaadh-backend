<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedOrganizer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            return $next($request);
        }

        $profile = $user?->organizerProfile;

        if (! $profile || ! $profile->isApproved()) {
            return redirect()
                ->route('organizer.dashboard')
                ->with('error', 'Your organizer account must be approved before managing events.');
        }

        return $next($request);
    }
}
