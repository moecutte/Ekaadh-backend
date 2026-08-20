<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDemoUsers();
        if (app()->environment('production')) {
            $this->seedProductionAdmin();
        }

        Setting::setValue('platform_name', 'Ekaadh');
        Setting::setValue('default_commission_rate', '10');
        Setting::setValue('service_fee', '1');
        Setting::setValue('payment_gateway', (string) env('PAYMENT_GATEWAY', 'waafipay'));
        Setting::setValue('private_ticket_price', '5');
        Setting::setValue('private_ticket_max', '500');
        Setting::setValue('private_premium_design_surcharge', '2');
        Setting::setValue('show_organizer_packages_on_front', '0');

        $this->call([
            OrganizerPackageSeeder::class,
            CategorySeeder::class,
            InvitationDesignSeeder::class,
            SupportFaqSeeder::class,
            EventSeeder::class,
        ]);
    }

    private function seedDemoUsers(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@ekaadh.com'],
            [
                'name' => 'Ekaadh Admin',
                'phone' => '+252630000001',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
                'status' => 'active',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'organizer@ekaadh.com'],
            [
                'name' => 'Horizon Events',
                'phone' => '+252630000010',
                'password' => 'password',
                'role' => User::ROLE_ORGANIZER,
                'status' => 'active',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'customer@ekaadh.com'],
            [
                'name' => 'Amina Hassan',
                'phone' => '+252632345678',
                'password' => 'password',
                'role' => User::ROLE_CUSTOMER,
                'status' => 'active',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'staff@ekaadh.com'],
            [
                'name' => 'Gate Staff',
                'phone' => '+252630000002',
                'password' => 'password',
                'role' => User::ROLE_STAFF,
                'status' => 'active',
            ]
        );
    }

    private function seedProductionAdmin(): void
    {
        $email = trim((string) env('ADMIN_EMAIL', ''));
        $password = (string) env('ADMIN_PASSWORD', '');
        $phone = trim((string) env('ADMIN_PHONE', ''));

        if ($email === '' || strlen($password) < 8) {
            $this->command?->warn('Production seed skipped demo users. Set ADMIN_EMAIL and ADMIN_PASSWORD (min 8) to create the first admin.');

            return;
        }

        User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Ekaadh Admin',
                'phone' => $phone !== '' ? $phone : null,
                'password' => $password,
                'role' => User::ROLE_ADMIN,
                'status' => 'active',
            ]
        );
    }
}
