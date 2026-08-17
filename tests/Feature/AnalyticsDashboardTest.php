<?php

namespace Tests\Feature;

use App\Models\MetricEvent;
use App\Services\PersistMetricEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Junges\Kafka\Facades\Kafka;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_vue_dashboard_shell_is_available(): void
    {
        $this->get('/')->assertOk()->assertSee('<div id="app"></div>', false);
    }

    public function test_an_event_is_published_to_kafka(): void
    {
        if (! defined('RD_KAFKA_PARTITION_UA')) {
            define('RD_KAFKA_PARTITION_UA', -1);
        }
        Kafka::fake();
        $payload = ['event_id' => (string) Str::uuid(), 'type' => 'sale', 'value' => 49.99, 'source' => 'checkout', 'occurred_at' => now()->toIso8601String()];
        $this->postJson('/api/events', $payload)->assertAccepted()->assertJsonPath('status', 'queued');
        Kafka::assertPublishedOn('analytics-events');
        $this->assertDatabaseCount('metric_events', 0);
    }

    public function test_consumed_events_are_persisted_idempotently(): void
    {
        $payload = ['event_id' => (string) Str::uuid(), 'type' => 'sale', 'value' => 49.99, 'source' => 'checkout', 'occurred_at' => now()->toIso8601String()];
        $persist = app(PersistMetricEvent::class);
        $persist->handle($payload);
        $persist->handle($payload);
        $this->assertDatabaseCount('metric_events', 1);
    }

    public function test_dashboard_api_aggregates_events(): void
    {
        MetricEvent::create(['event_id' => (string) Str::uuid(), 'type' => 'sale', 'value' => 125, 'source' => 'web', 'occurred_at' => now()]);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.cards.0.label', 'Revenue')->assertJsonPath('data.cards.0.value', 125);
    }
}
