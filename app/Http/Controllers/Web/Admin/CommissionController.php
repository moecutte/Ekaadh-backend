<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrganizerProfile;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionController extends Controller
{
    public function edit(): View
    {
        $defaultRate = (float) Setting::getValue('default_commission_rate', 10);
        $serviceFee = (float) Setting::getValue('service_fee', 1);
        $organizers = OrganizerProfile::query()
            ->with(['user', 'package'])
            ->where('approval_status', 'approved')
            ->orderBy('business_name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.commission', compact('defaultRate', 'serviceFee', 'organizers'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'default_commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_fee' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        Setting::setValue('default_commission_rate', $data['default_commission_rate']);
        Setting::setValue('service_fee', $data['service_fee']);

        return back()->with('success', 'Platform commission settings saved.');
    }
}
