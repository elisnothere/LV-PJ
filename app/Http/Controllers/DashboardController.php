<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService)
    {
    }

    public function __invoke()
    {
        return view('dashboard', $this->dashboardService->summary());
    }
}
