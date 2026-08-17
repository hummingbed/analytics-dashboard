<?php

namespace App\Services;

use App\Models\MetricEvent;

class PersistMetricEvent
{
    public function handle(array $payload): MetricEvent
    {
        return MetricEvent::firstOrCreate(
            ['event_id' => $payload['event_id']],
            collect($payload)->except('event_id')->all(),
        );
    }
}
