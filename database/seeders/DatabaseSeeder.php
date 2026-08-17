<?php

namespace Database\Seeders;

use App\Models\MetricEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $sources = ['web', 'mobile', 'checkout', 'campaign'];

        foreach (range(0, 23) as $hoursAgo) {
            $time = now()->subHours($hoursAgo)->startOfHour();
            foreach (range(1, random_int(5, 14)) as $index) {
                $paths = ['/pricing', '/products', '/checkout'];
                MetricEvent::create(['event_id' => (string) Str::uuid(), 'type' => 'page_view', 'value' => 1, 'source' => $sources[array_rand($sources)], 'metadata' => ['path' => $paths[array_rand($paths)]], 'occurred_at' => $time->copy()->addMinutes(random_int(0, 59))]);
            }
            foreach (range(1, random_int(1, 4)) as $index) {
                $channels = ['web', 'mobile'];
                MetricEvent::create(['event_id' => (string) Str::uuid(), 'type' => 'sale', 'value' => random_int(2500, 18000) / 100, 'source' => $channels[array_rand($channels)], 'metadata' => ['currency' => 'USD', 'order_id' => 'ORD-'.random_int(1000, 9999)], 'occurred_at' => $time->copy()->addMinutes(random_int(0, 59))]);
            }
            MetricEvent::create(['event_id' => (string) Str::uuid(), 'type' => 'operation', 'value' => random_int(80, 900), 'source' => 'api', 'metadata' => ['status' => random_int(1, 100) <= 94 ? 'success' : 'failed'], 'occurred_at' => $time->copy()->addMinutes(random_int(0, 59))]);
        }
    }
}
