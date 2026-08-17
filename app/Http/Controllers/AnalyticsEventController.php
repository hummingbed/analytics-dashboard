<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMetricEventRequest;
use App\Models\MetricEvent;
use Illuminate\Http\JsonResponse;

class AnalyticsEventController extends Controller
{
    public function store(StoreMetricEventRequest $request): JsonResponse
    {
        $event = MetricEvent::firstOrCreate(
            ['event_id' => $request->validated('event_id')],
            $request->safe()->except('event_id'),
        );

        return response()->json([
            'data' => $event,
            'duplicate' => ! $event->wasRecentlyCreated,
        ], $event->wasRecentlyCreated ? 201 : 200);
    }
}
