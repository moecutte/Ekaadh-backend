<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private OtpService $otp) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'otp_token' => ['required', 'string', 'max:80'],
        ]);

        $phone = Phone::normalize($data['phone']);
        $this->otp->consumeVerified($phone, OtpService::PURPOSE_REGISTER, $data['otp_token']);

        if (User::query()->whereIn('phone', Phone::variants($phone))->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['This phone number already has an account. Please sign in.'],
            ]);
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

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Account created successfully.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $login = $data['login'];
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

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'login' => ['Your account is not active.'],
            ]);
        }

        $token = $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken;

        return response()->json([
            'message' => 'Signed in successfully.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Signed out successfully.',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => $data['password'],
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email,'.$user->id,
            ],
            'push_notifications_enabled' => ['sometimes', 'boolean'],
        ]);

        $updates = [
            'name' => $data['name'],
        ];

        if (array_key_exists('email', $data) && filled($data['email'])) {
            $updates['email'] = $data['email'];
        }

        if (array_key_exists('push_notifications_enabled', $data)) {
            $updates['push_notifications_enabled'] = $data['push_notifications_enabled'];
        }

        $user->update($updates);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    public function destroyAccount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user->isCustomer()) {
            throw ValidationException::withMessages([
                'password' => ['This account cannot be deleted from the app.'],
            ]);
        }

        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The current password is incorrect.'],
            ]);
        }

        $user->tokens()->delete();
        $user->deviceTokens()->delete();
        $user->notifications()->delete();

        $user->update([
            'name' => 'Deleted account',
            'email' => 'deleted-'.$user->id.'-'.time().'@ekaadh.invalid',
            'phone' => null,
            'password' => str()->password(40),
            'status' => 'inactive',
            'avatar' => null,
            'push_notifications_enabled' => false,
        ]);

        return response()->json([
            'message' => 'Your account has been deleted.',
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'status' => $user->status,
            'avatar' => $user->avatar,
            'push_notifications_enabled' => (bool) $user->push_notifications_enabled,
        ];
    }

    private function phoneEmail(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: uniqid('user');

        return "{$digits}@ekaadh.local";
    }
}
