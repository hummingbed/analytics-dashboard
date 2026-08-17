<?php

namespace App\Http\Controllers;

use App\Services\TransactionDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard');
    }

    public function data(TransactionDashboard $dashboard): JsonResponse
    {
        return response()->json(['data' => $dashboard->snapshot()]);
    }
}
