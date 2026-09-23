<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrganizerProfile;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $defaultRate = (float) Setting::getValue('default_commission_rate', 5);

        return view('admin.settings.edit', [
            'platformName' => (string) Setting::getValue('platform_name', 'Ekaadh'),
            'privateTicketPrice' => (float) Setting::getValue('private_ticket_price', 0.8),
            'privateTicketMax' => (int) Setting::getValue('private_ticket_max', 500),
            'privatePremiumSurcharge' => (float) Setting::getValue('private_premium_design_surcharge', 0.2),
            'defaultRate' => $defaultRate,
            'serviceFee' => (float) Setting::getValue('service_fee', 1),
            'freeTicketFee' => (float) Setting::getValue('free_ticket_organizer_fee', 0.25),
            'showOrganizerPackagesOnFront' => filter_var(
                Setting::getValue('show_organizer_packages_on_front', '0'),
                FILTER_VALIDATE_BOOLEAN
            ),
            'organizers' => OrganizerProfile::query()
                ->with(['user', 'package'])
                ->where('approval_status', 'approved')
                ->orderBy('business_name')
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'platform_name' => ['required', 'string', 'max:80'],
            'private_ticket_price' => ['required', 'numeric', 'min:0', 'max:1000'],
            'private_ticket_max' => ['required', 'integer', 'min:1', 'max:100000'],
            'private_premium_design_surcharge' => ['required', 'numeric', 'min:0', 'max:1000'],
            'default_commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_fee' => ['required', 'numeric', 'min:0', 'max:100'],
            'free_ticket_organizer_fee' => ['required', 'numeric', 'min:0', 'max:100'],
            'show_organizer_packages_on_front' => ['nullable', 'boolean'],
        ]);

        Setting::setValue('platform_name', $data['platform_name']);
        Setting::setValue('private_ticket_price', $data['private_ticket_price']);
        Setting::setValue('private_ticket_max', $data['private_ticket_max']);
        Setting::setValue('private_premium_design_surcharge', $data['private_premium_design_surcharge']);
        Setting::setValue('default_commission_rate', $data['default_commission_rate']);
        Setting::setValue('service_fee', $data['service_fee']);
        Setting::setValue('free_ticket_organizer_fee', $data['free_ticket_organizer_fee']);
        Setting::setValue('show_organizer_packages_on_front', $request->boolean('show_organizer_packages_on_front') ? '1' : '0');

        return back()->with('success', 'System settings saved.');
    }
}
