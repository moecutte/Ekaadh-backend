<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private OtpService $otp) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $credentials['login'];
        $normalized = Phone::normalize($login);

        $user = User::query()
            ->where(function ($q) use ($login, $normalized) {
                $q->where('email', $login);
                if ($normalized !== '') {
                    $q->orWhereIn('phone', Phone::variants($normalized));
                } else {
                    $q->orWhere('phone', $login);
                }
            })
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['login' => 'Invalid credentials.'])->onlyInput('login');
        }

        if (! $user->isCustomer()) {
            return back()->withErrors([
                'login' => 'This sign-in is for customers. Organizers and admins should use their portal.',
            ])->onlyInput('login');
        }

        if (! $user->isActive()) {
            return back()->withErrors(['login' => 'Your account is not active.'])->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('tickets.index'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'otp_token' => ['required', 'string', 'max:80'],
        ]);

        $phone = Phone::normalize($data['phone']);

        try {
            $this->otp->consumeVerified($phone, OtpService::PURPOSE_REGISTER, $data['otp_token']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        if (User::query()->whereIn('phone', Phone::variants($phone))->exists()) {
            return back()->withErrors([
                'phone' => 'This phone number already has an account. Please sign in.',
            ])->withInput();
        }

        $email = $data['email'] ?? $this->phoneEmail($phone);

        $user = User::query()->create([
            'name' => $data['name'],
            'phone' => $phone,
            'email' => $email,
            'password' => $data['password'],
            'role' => User::ROLE_CUSTOMER,
            'status' => 'active',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Welcome to Ekaadh. You are signed in.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function phoneEmail(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: uniqid('user');

        return "{$digits}@ekaadh.local";
    }
}
