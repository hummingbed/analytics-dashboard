<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardMetrics $metrics): View
    {
        return view('dashboard', ['snapshot' => $metrics->snapshot()]);
    }

    public function data(DashboardMetrics $metrics): JsonResponse
    {
        return response()->json(['data' => $metrics->snapshot()]);
    }
}
