<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ScheduleController extends Controller
{
    public function __invoke(): View
    {
        return view('schedule');
    }
}
