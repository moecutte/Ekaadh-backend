<?php

namespace App\Http\Controllers\Web\Concerns;

use App\Models\User;
use App\Support\AvatarUpload;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait UpdatesAccountProfile
{
    /**
     * @return array<string, mixed>
     */
    protected function accountRules(User $user, bool $requirePhone = false): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => array_merge(
                $requirePhone ? ['required'] : ['nullable'],
                ['string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)]
            ),
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_avatar' => ['sometimes', 'boolean'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function applyAccountUpdates(User $user, Request $request, array $data): void
    {
        $updates = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => filled($data['phone'] ?? null) ? $data['phone'] : null,
        ];

        if (! empty($data['password'])) {
            $updates['password'] = $data['password'];
        }

        $currentPath = $user->getRawOriginal('avatar');

        if ($request->boolean('remove_avatar') && ! $request->hasFile('avatar')) {
            AvatarUpload::delete($currentPath);
            $updates['avatar'] = null;
        }

        if ($request->hasFile('avatar')) {
            AvatarUpload::delete($currentPath);
            $updates['avatar'] = AvatarUpload::store($request->file('avatar'));
        }

        $user->update($updates);
    }
}
