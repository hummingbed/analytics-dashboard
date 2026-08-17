<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard');
    }

    public function data(DashboardMetrics $metrics): JsonResponse
    {
        return response()->json(['data' => $metrics->snapshot()]);
    }
}
