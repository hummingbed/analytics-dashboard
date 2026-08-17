<?php

namespace Tests\Feature;

use App\Models\MetricEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_is_available(): void
    {
        $this->get('/')->assertOk()->assertSee('Your business, right now.');
    }

    public function test_an_event_can_be_ingested_idempotently(): void
    {
        $payload = ['event_id' => (string) Str::uuid(), 'type' => 'sale', 'value' => 49.99, 'source' => 'checkout', 'occurred_at' => now()->toIso8601String()];
        $this->postJson('/api/events', $payload)->assertCreated()->assertJsonPath('duplicate', false);
        $this->postJson('/api/events', $payload)->assertOk()->assertJsonPath('duplicate', true);
        $this->assertDatabaseCount('metric_events', 1);
    }

    public function test_dashboard_api_aggregates_events(): void
    {
        MetricEvent::create(['event_id' => (string) Str::uuid(), 'type' => 'sale', 'value' => 125, 'source' => 'web', 'occurred_at' => now()]);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.cards.0.label', 'Revenue')->assertJsonPath('data.cards.0.value', 125);
    }
}
