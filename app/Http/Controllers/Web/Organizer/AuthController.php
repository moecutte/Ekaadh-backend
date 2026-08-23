<?php

namespace App\Http\Controllers\Web\Organizer;

use App\Http\Controllers\Controller;
use App\Models\OrganizerPackage;
use App\Models\OrganizerProfile;
use App\Models\User;
use App\Services\PanelNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        return view('organizer.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate($this->registrationRules());

        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => User::ROLE_ORGANIZER,
                'status' => 'active',
            ]);

            OrganizerProfile::query()->create([
                'user_id' => $user->id,
                'business_name' => $data['business_name'],
                'business_phone' => $data['business_phone'] ?? $data['phone'],
                'city' => $data['city'],
                'business_description' => $data['business_description'],
                'package_id' => OrganizerPackage::defaultPackage()?->id,
                'approval_status' => 'approved',
                'approved_at' => now(),
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        $user->load('organizerProfile');
        if ($profile = $user->organizerProfile) {
            app(PanelNotifier::class)->organizerRegistered($profile);
        }

        return redirect()
            ->route('organizer.dashboard')
            ->with('success', 'Your organizer account is ready. You can create events right away.');
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
    public static function registrationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'business_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'city' => ['required', 'string', 'max:100'],
            'business_description' => ['required', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ];
    }
}
