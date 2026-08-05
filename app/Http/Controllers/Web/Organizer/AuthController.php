<?php

namespace App\Http\Controllers\Web\Organizer;

use App\Http\Controllers\Controller;
use App\Models\OrganizerPackage;
use App\Models\OrganizerProfile;
use App\Models\User;
use App\Support\OrganizerDocuments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('organizer.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $credentials['login'])
            ->orWhere('phone', $credentials['login'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['login' => 'Invalid credentials.'])->onlyInput('login');
        }

        if ($user->role !== User::ROLE_ORGANIZER && $user->role !== User::ROLE_ADMIN) {
            return back()->withErrors(['login' => 'This portal is for organizers only.'])->onlyInput('login');
        }

        if (! $user->isActive()) {
            return back()->withErrors(['login' => 'Your account is not active.'])->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('organizer.dashboard'));
    }

    public function showRegister(): View
    {
        return view('organizer.auth.register', [
            'idTypes' => OrganizerProfile::ID_TYPES,
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate($this->applicationRules());

        $user = DB::transaction(function () use ($data, $request) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => User::ROLE_ORGANIZER,
                'status' => 'active',
            ]);

            $documents = OrganizerDocuments::store($user->id, [
                'id_type' => $data['id_type'],
                'id_front' => $request->file('id_document_front'),
                'id_back' => $request->file('id_document_back'),
                'business_license' => $request->file('business_license'),
            ]);

            OrganizerProfile::query()->create([
                'user_id' => $user->id,
                'business_name' => $data['business_name'],
                'business_phone' => $data['business_phone'] ?? $data['phone'],
                'city' => $data['city'],
                'business_description' => $data['business_description'],
                'id_number' => $data['id_number'],
                'documents' => $documents,
                'package_id' => OrganizerPackage::defaultPackage()?->id,
                'approval_status' => 'pending',
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('organizer.dashboard')
            ->with('success', 'Application submitted with your ID documents. An admin will review your account.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('organizer.login');
    }

    /**
     * @return array<string, mixed>
     */
    public static function applicationRules(bool $requireNewIdFront = true): array
    {
        $file = ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];

        return [
            'name' => ['required', 'string', 'max:120'],
            'business_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'city' => ['required', 'string', 'max:100'],
            'business_description' => ['required', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'id_type' => ['required', Rule::in(array_keys(OrganizerProfile::ID_TYPES))],
            'id_number' => ['required', 'string', 'max:80'],
            'id_document_front' => array_merge(
                $requireNewIdFront ? ['required'] : ['nullable'],
                $file
            ),
            'id_document_back' => [
                Rule::requiredIf(fn () => request('id_type') === 'national_id' && $requireNewIdFront),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
            'business_license' => array_merge(['nullable'], $file),
            'terms' => ['accepted'],
        ];
    }
}
