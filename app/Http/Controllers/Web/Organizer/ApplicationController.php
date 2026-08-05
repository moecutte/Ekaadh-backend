<?php

namespace App\Http\Controllers\Web\Organizer;

use App\Http\Controllers\Controller;
use App\Models\OrganizerProfile;
use App\Support\OrganizerDocuments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $profile = $this->profile($request);

        if ($profile->isApproved()) {
            return redirect()
                ->route('organizer.dashboard')
                ->with('success', 'Your organizer account is already approved.');
        }

        return view('organizer.application.edit', [
            'profile' => $profile,
            'idTypes' => OrganizerProfile::ID_TYPES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $profile = $this->profile($request);

        if ($profile->isApproved()) {
            return redirect()->route('organizer.dashboard');
        }

        $hasFront = $profile->hasIdentityDocuments();

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:160'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'city' => ['required', 'string', 'max:100'],
            'business_description' => ['required', 'string', 'max:500'],
            'id_type' => ['required', Rule::in(array_keys(OrganizerProfile::ID_TYPES))],
            'id_number' => ['required', 'string', 'max:80'],
            'id_document_front' => array_merge(
                $hasFront ? ['nullable'] : ['required'],
                ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120']
            ),
            'id_document_back' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
                Rule::requiredIf(function () use ($request, $profile, $hasFront) {
                    if ($request->input('id_type') !== 'national_id') {
                        return false;
                    }
                    if ($request->file('id_document_back')) {
                        return false;
                    }

                    return ! $hasFront || empty($profile->documents['id_back'] ?? null);
                }),
            ],
            'business_license' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'terms' => ['accepted'],
        ]);

        $documents = OrganizerDocuments::store($profile->user_id, [
            'id_type' => $data['id_type'],
            'id_front' => $request->file('id_document_front'),
            'id_back' => $request->file('id_document_back'),
            'business_license' => $request->file('business_license'),
        ], $profile->documents);

        $profile->update([
            'business_name' => $data['business_name'],
            'business_phone' => $data['business_phone'] ?: $profile->business_phone,
            'city' => $data['city'],
            'business_description' => $data['business_description'],
            'id_number' => $data['id_number'],
            'documents' => $documents,
            'approval_status' => 'pending',
            'rejection_reason' => null,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return redirect()
            ->route('organizer.dashboard')
            ->with('success', 'Application updated. An admin will review your documents again.');
    }

    private function profile(Request $request): OrganizerProfile
    {
        $user = $request->user();
        $profile = $user->organizerProfile;

        abort_unless($profile, 404);

        return $profile;
    }
}
