<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OrganizerPackage;
use Illuminate\View\View;

class OrganizerLandingController extends Controller
{
    public function __invoke(): View
    {
        $packages = OrganizerPackage::query()->active()->ordered()->get();

        return view('organizers.index', compact('packages'));
    }
}
