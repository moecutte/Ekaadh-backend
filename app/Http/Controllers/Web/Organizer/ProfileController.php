<?php

namespace App\Http\Controllers\Web\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\UpdatesAccountProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use UpdatesAccountProfile;

    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('organizer.profile.edit', [
            'user' => $user,
            'profile' => $user->organizerProfile,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->organizerProfile;

        $rules = $this->accountRules($user, requirePhone: true);

        if ($profile) {
            $rules = array_merge($rules, [
                'business_name' => ['required', 'string', 'max:160'],
                'business_phone' => ['nullable', 'string', 'max:30'],
                'city' => ['required', 'string', 'max:100'],
                'business_description' => ['required', 'string', 'max:500'],
            ]);
        }

        $data = $request->validate($rules);
        $this->applyAccountUpdates($user, $request, $data);

        if ($profile) {
            $profile->update([
                'business_name' => $data['business_name'],
                'business_phone' => $data['business_phone'] ?: $profile->business_phone,
                'city' => $data['city'],
                'business_description' => $data['business_description'],
            ]);
        }

        return back()->with('success', 'Profile saved.');
    }
}
