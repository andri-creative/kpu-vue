<?php

namespace App\Http\Controllers;

use App\Support\DashboardMetrics;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(DashboardMetrics $metrics): Response
    {
        return Inertia::render('Dashboard', $metrics->all());
    }
}
