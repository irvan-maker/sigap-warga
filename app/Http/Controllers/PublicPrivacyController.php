<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

final class PublicPrivacyController extends Controller
{
    public function __invoke(): View
    {
        return view('public.privacy');
    }
}
