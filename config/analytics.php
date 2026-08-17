<?php

return [
    'kafka_brokers' => env('KAFKA_BROKERS', 'kafka:9092'),
    'kafka_topic' => env('KAFKA_TOPIC', 'analytics-events'),
    'kafka_consumer_group' => env('KAFKA_CONSUMER_GROUP', 'pulse-dashboard'),
];
