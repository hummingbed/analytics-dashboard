<?php

namespace App\Services;

use App\Models\MetricEvent;
use Carbon\CarbonImmutable;

class DashboardMetrics
{
    public function snapshot(): array
    {
        $now = CarbonImmutable::now();
        $start = $now->startOfDay();
        $events = MetricEvent::query()->where('occurred_at', '>=', $start)->get();
        $previous = MetricEvent::query()
            ->whereBetween('occurred_at', [$start->subDay(), $start])
            ->get();

        $sales = $events->where('type', 'sale');
        $views = $events->where('type', 'page_view');
        $clicks = $events->where('type', 'click');
        $operations = $events->where('type', 'operation');

        $cards = [
            $this->card('Revenue', (float) $sales->sum('value'), (float) $previous->where('type', 'sale')->sum('value'), 'currency'),
            $this->card('Visitors', $views->count(), $previous->where('type', 'page_view')->count(), 'number'),
            $this->card('Clicks', $clicks->count(), $previous->where('type', 'click')->count(), 'number'),
            $this->card('Success rate', $this->successRate($operations), $this->successRate($previous->where('type', 'operation')), 'percent'),
        ];

        $hours = collect(range(0, 23))->map(function (int $hour) use ($events, $start) {
            $bucket = $events->filter(fn (MetricEvent $event) => $event->occurred_at->hour === $hour);

            return [
                'label' => $start->setHour($hour)->format('H:00'),
                'revenue' => round((float) $bucket->where('type', 'sale')->sum('value'), 2),
                'traffic' => $bucket->where('type', 'page_view')->count(),
            ];
        });

        return [
            'cards' => $cards,
            'series' => $hours,
            'sources' => $events->groupBy('source')->map->count()->sortDesc()->take(5),
            'recent' => MetricEvent::query()->latest('occurred_at')->limit(8)->get(),
            'updated_at' => $now->toIso8601String(),
        ];
    }

    private function card(string $label, float|int $value, float|int $previous, string $format): array
    {
        $change = $previous > 0 ? (($value - $previous) / $previous) * 100 : ($value > 0 ? 100 : 0);

        return compact('label', 'value', 'format') + ['change' => round($change, 1)];
    }

    private function successRate($events): float
    {
        if ($events->isEmpty()) {
            return 0;
        }

        return round($events->filter(fn (MetricEvent $event) => data_get($event->metadata, 'status') === 'success')->count() / $events->count() * 100, 1);
    }
}
