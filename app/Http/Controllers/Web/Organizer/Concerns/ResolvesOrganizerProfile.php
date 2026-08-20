<?php

namespace App\Http\Controllers\Web\Organizer\Concerns;

use App\Models\OrganizerProfile;
use Illuminate\Http\Exceptions\HttpResponseException;

trait ResolvesOrganizerProfile
{
    private function organizerProfile(): OrganizerProfile
    {
        $user = auth()->user();
        $profile = $user?->organizerProfile;

        if ($profile) {
            return $profile->loadMissing('package');
        }

        throw new HttpResponseException(
            redirect()
                ->route($user?->isAdmin() ? 'admin.dashboard' : 'organizer.dashboard')
                ->with('error', $user?->isAdmin()
                    ? 'Create public events from an organizer account. Review them under Admin → Events.'
                    : 'Your organizer profile is missing. Complete your application or contact support.')
        );
    }
}
