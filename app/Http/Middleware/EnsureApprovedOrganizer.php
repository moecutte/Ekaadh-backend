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

        $profile = $user?->organizerProfile;

        if ($user?->isAdmin()) {
            if ($profile) {
                return $next($request);
            }

            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Create public events from an organizer account. Review them under Admin → Events.');
        }

        if (! $profile || ! $profile->isApproved()) {
            return redirect()
                ->route('organizer.dashboard')
                ->with('error', 'Your organizer account must be approved before managing events.');
        }

        return $next($request);
    }
}
