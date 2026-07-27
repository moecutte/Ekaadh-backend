<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@ekaadh.com'],
            [
                'name' => 'Ekaadh Admin',
                'phone' => '+252610000001',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
                'status' => 'active',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'customer@ekaadh.com'],
            [
                'name' => 'Amina Hassan',
                'phone' => '+252612345678',
                'password' => 'password',
                'role' => User::ROLE_CUSTOMER,
                'status' => 'active',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'staff@ekaadh.com'],
            [
                'name' => 'Gate Staff',
                'phone' => '+252610000002',
                'password' => 'password',
                'role' => User::ROLE_STAFF,
                'status' => 'active',
            ]
        );

        Setting::setValue('platform_name', 'Ekaadh');
        Setting::setValue('default_commission_rate', '10');
        Setting::setValue('service_fee', '1');
        Setting::setValue('payment_gateway', 'mock');
        Setting::setValue('private_ticket_price', '5');
        Setting::setValue('private_ticket_max', '500');
        Setting::setValue('private_premium_design_surcharge', '2');

        $this->call(EventSeeder::class);
    }
}
