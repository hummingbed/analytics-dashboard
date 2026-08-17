<?php

namespace App\Console\Commands;

use App\Models\MetricEvent;
use Illuminate\Console\Command;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Facades\Kafka;

class ConsumeAnalyticsEvents extends Command
{
    protected $signature = 'kafka:consume-analytics';

    protected $description = 'Consume analytics events from Kafka and persist them';

    public function handle(): int
    {
        $this->components->info('Listening for analytics events...');

        Kafka::consumer([config('analytics.kafka_topic')])
            ->withBrokers(config('analytics.kafka_brokers'))
            ->withConsumerGroupId(config('analytics.kafka_consumer_group'))
            ->withAutoCommit()
            ->withHandler(function (ConsumerMessage $message): void {
                $event = $message->getBody();

                MetricEvent::firstOrCreate(
                    ['event_id' => $event['event_id']],
                    $event,
                );
            })
            ->withOptions(['auto.offset.reset' => 'earliest'])
            ->build()
            ->consume();

        return self::SUCCESS;
    }
}
