<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\CarbonImmutable;

class TransactionDashboard
{
    public function snapshot(): array
    {
        $startOfDay = CarbonImmutable::now()->startOfDay();
        $summary = Transaction::query()
            ->where('transacted_at', '>=', $startOfDay)
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_value')
            ->selectRaw("SUM(CASE WHEN status = 'successful' THEN 1 ELSE 0 END) as successful_count")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count")
            ->first();

        return [
            'summary' => [
                'total_value' => round((float) $summary->total_value, 2),
                'transaction_count' => (int) $summary->transaction_count,
                'successful_count' => (int) $summary->successful_count,
                'failed_count' => (int) $summary->failed_count,
            ],
            'transactions' => Transaction::query()->latest('transacted_at')->limit(20)->get(),
            'updated_at' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
