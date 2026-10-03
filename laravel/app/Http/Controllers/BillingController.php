<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class BillingController extends Controller
{
    public function index(): View
    {
        return view('billing.index');
    }
}
