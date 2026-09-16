<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ShellController extends Controller
{
    public function __invoke(): View
    {
        return view('shell');
    }
}
