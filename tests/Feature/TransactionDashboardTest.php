<?php

namespace Tests\Feature;

use App\Events\TransactionCreated;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use Tests\TestCase;

class TransactionDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('RD_KAFKA_PARTITION_UA')) {
            define('RD_KAFKA_PARTITION_UA', -1);
        }
    }

    public function test_dashboard_page_loads_the_vue_application(): void
    {
        $this->get('/')->assertOk()->assertSee('id="app"', false);
    }

    public function test_transaction_endpoint_publishes_a_message_to_kafka(): void
    {
        Kafka::fake();
        $transaction = $this->transactionPayload();

        $this->postJson('/api/transactions', $transaction)
            ->assertAccepted()
            ->assertJsonPath('transaction_id', $transaction['transaction_id'])
            ->assertJsonPath('status', 'queued');

        Kafka::assertPublishedOn('user-transactions', callback: fn (ProducerMessage $message) => $message->getBody() === $transaction);
    }

    public function test_consumer_persists_each_transaction_only_once(): void
    {
        Event::fake([TransactionCreated::class]);
        Kafka::fake();
        $transaction = $this->transactionPayload();
        $message = new ConsumedMessage('user-transactions', 0, [], $transaction, $transaction['transaction_id'], 1, now()->timestamp);

        Kafka::shouldReceiveMessages([$message, $message]);
        $this->artisan('kafka:consume-transactions')->assertSuccessful();

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('transactions', ['transaction_id' => $transaction['transaction_id']]);
        Event::assertDispatchedTimes(TransactionCreated::class, 2);
        Event::assertDispatched(
            TransactionCreated::class,
            fn (TransactionCreated $event) => $event->broadcastWith()['counts_toward_today'] === true,
        );
    }

    public function test_dashboard_returns_transaction_summary_and_recent_rows(): void
    {
        Transaction::create($this->transactionPayload(['amount' => 250, 'status' => 'successful']));
        Transaction::create($this->transactionPayload(['transaction_id' => (string) Str::uuid(), 'amount' => 75, 'status' => 'failed']));

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.total_value', 325)
            ->assertJsonPath('data.summary.transaction_count', 2)
            ->assertJsonPath('data.summary.successful_count', 1)
            ->assertJsonPath('data.summary.failed_count', 1)
            ->assertJsonCount(2, 'data.transactions');
    }

    private function transactionPayload(array $overrides = []): array
    {
        return array_merge([
            'transaction_id' => (string) Str::uuid(),
            'user_name' => 'Michael',
            'amount' => 125.50,
            'type' => 'credit',
            'status' => 'successful',
            'description' => 'Wallet deposit',
            'transacted_at' => now()->toIso8601String(),
        ], $overrides);
    }
}
