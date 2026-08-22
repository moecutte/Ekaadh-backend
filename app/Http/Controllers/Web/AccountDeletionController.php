<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AccountDeletionController extends Controller
{
    public function __invoke(): View
    {
        return view('legal.account-deletion', [
            'supportEmail' => 'hello@ekaadh.com',
        ]);
    }
}
