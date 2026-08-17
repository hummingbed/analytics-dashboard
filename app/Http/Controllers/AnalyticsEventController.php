<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMetricEventRequest;
use Illuminate\Http\JsonResponse;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class AnalyticsEventController extends Controller
{
    public function store(StoreMetricEventRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $message = new Message(
            headers: ['event-type' => $payload['type']],
            body: $payload,
            key: $payload['event_id'],
        );

        Kafka::publish(config('analytics.kafka_brokers'))
            ->onTopic(config('analytics.kafka_topic'))
            ->withMessage($message)
            ->send();

        return response()->json([
            'event_id' => $payload['event_id'],
            'status' => 'queued',
            'topic' => config('analytics.kafka_topic'),
        ], 202);
    }
}
