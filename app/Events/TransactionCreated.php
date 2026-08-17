<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Transaction $transaction) {}

    public function broadcastOn(): array
    {
        return [new Channel('transactions')];
    }

    public function broadcastAs(): string
    {
        return 'transaction.created';
    }

    public function broadcastWith(): array
    {
        return [
            'transaction' => $this->transaction->toArray(),
            'counts_toward_today' => $this->transaction->transacted_at->isToday(),
            'broadcasted_at' => now()->toIso8601String(),
        ];
    }
}
