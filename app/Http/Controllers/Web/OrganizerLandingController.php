<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OrganizerPackage;
use App\Models\Setting;
use Illuminate\View\View;

class OrganizerLandingController extends Controller
{
    public function __invoke(): View
    {
        $showPackages = filter_var(
            Setting::getValue('show_organizer_packages_on_front', '0'),
            FILTER_VALIDATE_BOOLEAN
        );

        $packages = $showPackages
            ? OrganizerPackage::query()->active()->ordered()->get()
            : collect();

        return view('organizers.index', [
            'packages' => $packages,
            'showPackages' => $showPackages,
        ]);
    }
}
