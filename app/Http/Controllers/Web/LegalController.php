<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return view('legal.privacy', [
            'supportEmail' => 'hello@ekaadh.com',
        ]);
    }

    public function terms(): View
    {
        return view('legal.terms', [
            'supportEmail' => 'hello@ekaadh.com',
        ]);
    }
}
