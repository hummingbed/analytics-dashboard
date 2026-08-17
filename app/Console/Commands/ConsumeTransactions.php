<?php

namespace App\Console\Commands;

use App\Events\TransactionCreated;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Facades\Kafka;

class ConsumeTransactions extends Command
{
    protected $signature = 'kafka:consume-transactions';

    protected $description = 'Consume transactions from Kafka and persist them';

    public function handle(): int
    {
        $this->components->info('Listening for transactions...');

        Kafka::consumer([config('analytics.kafka_topic')])
            ->withBrokers(config('analytics.kafka_brokers'))
            ->withConsumerGroupId(config('analytics.kafka_consumer_group'))
            ->withAutoCommit()
            ->withHandler(function (ConsumerMessage $message): void {
                $transaction = $message->getBody();

                $savedTransaction = Transaction::firstOrCreate(
                    ['transaction_id' => $transaction['transaction_id']],
                    $transaction,
                );

                if ($savedTransaction->wasRecentlyCreated) {
                    TransactionCreated::dispatch($savedTransaction);
                }
            })
            ->withOptions(['auto.offset.reset' => 'earliest'])
            ->build()
            ->consume();

        return self::SUCCESS;
    }
}
