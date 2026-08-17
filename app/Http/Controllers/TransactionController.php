<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use Illuminate\Http\JsonResponse;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class TransactionController extends Controller
{
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $transaction = $request->validated();

        Kafka::publish(config('analytics.kafka_brokers'))
            ->onTopic(config('analytics.kafka_topic'))
            ->withMessage(new Message(
                headers: ['message-type' => 'transaction.created'],
                body: $transaction,
                key: $transaction['transaction_id'],
            ))
            ->send();

        return response()->json([
            'transaction_id' => $transaction['transaction_id'],
            'status' => 'queued',
            'topic' => config('analytics.kafka_topic'),
        ], 202);
    }
}
