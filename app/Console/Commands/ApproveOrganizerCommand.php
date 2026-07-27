<?php

namespace App\Console\Commands;

use App\Models\OrganizerProfile;
use App\Models\User;
use Illuminate\Console\Command;

class ApproveOrganizerCommand extends Command
{
    protected $signature = 'ekaadh:approve-organizer {email : Organizer user email}';

    protected $description = 'Approve a pending organizer application (until admin panel ships)';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $profile = $user->organizerProfile;
        if (! $profile) {
            $this->error('No organizer profile for this user.');

            return self::FAILURE;
        }

        $admin = User::query()->where('role', User::ROLE_ADMIN)->first();

        $profile->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin?->id,
            'rejection_reason' => null,
        ]);

        $user->update(['role' => User::ROLE_ORGANIZER]);

        $this->info("Approved organizer: {$profile->business_name} ({$user->email})");

        return self::SUCCESS;
    }
}
