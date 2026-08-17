<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\CarbonImmutable;

class TransactionDashboard
{
    public function snapshot(): array
    {
        $startOfDay = CarbonImmutable::now()->startOfDay();
        $transactions = Transaction::query()
            ->where('transacted_at', '>=', $startOfDay)
            ->get();

        return [
            'summary' => [
                'total_value' => round((float) $transactions->sum('amount'), 2),
                'transaction_count' => $transactions->count(),
                'successful_count' => $transactions->where('status', 'successful')->count(),
                'failed_count' => $transactions->where('status', 'failed')->count(),
            ],
            'transactions' => Transaction::query()->latest('transacted_at')->limit(20)->get(),
            'updated_at' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
