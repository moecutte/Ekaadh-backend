<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'platformName' => (string) Setting::getValue('platform_name', 'Ekaadh'),
            'privateTicketPrice' => (float) Setting::getValue('private_ticket_price', 5),
            'privateTicketMax' => (int) Setting::getValue('private_ticket_max', 500),
            'privatePremiumSurcharge' => (float) Setting::getValue('private_premium_design_surcharge', 2),
            'showOrganizerPackagesOnFront' => filter_var(
                Setting::getValue('show_organizer_packages_on_front', '0'),
                FILTER_VALIDATE_BOOLEAN
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'platform_name' => ['required', 'string', 'max:80'],
            'private_ticket_price' => ['required', 'numeric', 'min:0', 'max:1000'],
            'private_ticket_max' => ['required', 'integer', 'min:1', 'max:100000'],
            'private_premium_design_surcharge' => ['required', 'numeric', 'min:0', 'max:1000'],
            'show_organizer_packages_on_front' => ['nullable', 'boolean'],
        ]);

        Setting::setValue('platform_name', $data['platform_name']);
        Setting::setValue('private_ticket_price', $data['private_ticket_price']);
        Setting::setValue('private_ticket_max', $data['private_ticket_max']);
        Setting::setValue('private_premium_design_surcharge', $data['private_premium_design_surcharge']);
        Setting::setValue('show_organizer_packages_on_front', $request->boolean('show_organizer_packages_on_front') ? '1' : '0');

        return back()->with('success', 'System settings saved.');
    }
}
