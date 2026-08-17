<?php

namespace App\Kafka\Handlers;

use App\Services\PersistMetricEvent;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageConsumer;

class AnalyticsEventHandler implements Handler
{
    public function __construct(private readonly PersistMetricEvent $persist) {}

    public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $this->persist->handle($message->getBody());
    }
}
