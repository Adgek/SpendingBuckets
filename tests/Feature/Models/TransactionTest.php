<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_an_allocation_transaction(): void
    {
        $bucket = Bucket::factory()->fixed()->create();
        $deposit = Deposit::factory()->create();

        $transaction = Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $deposit->id,
            'amount' => 100000,
            'type' => 'allocation',
            'description' => 'Monthly allocation',
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'bucket_id' => $bucket->id,
            'deposit_id' => $deposit->id,
            'amount' => 100000,
            'type' => 'allocation',
        ]);
    }

    public function test_it_can_create_an_expense_transaction(): void
    {
        $bucket = Bucket::factory()->fixed()->create();

        $transaction = Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => null,
            'amount' => -4500,
            'type' => 'expense',
            'description' => 'Water bill payment',
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => -4500,
            'type' => 'expense',
            'deposit_id' => null,
        ]);
    }

    public function test_transaction_belongs_to_bucket(): void
    {
        $bucket = Bucket::factory()->fixed()->create();
        $transaction = Transaction::factory()->create(['bucket_id' => $bucket->id]);

        $this->assertTrue($transaction->bucket->is($bucket));
    }

    public function test_transaction_belongs_to_deposit(): void
    {
        $deposit = Deposit::factory()->create();
        $transaction = Transaction::factory()->create(['deposit_id' => $deposit->id]);

        $this->assertTrue($transaction->deposit->is($deposit));
    }

    public function test_transaction_deposit_is_nullable(): void
    {
        $transaction = Transaction::factory()->create(['deposit_id' => null]);

        $this->assertNull($transaction->deposit);
    }

    public function test_created_at_is_automatically_set(): void
    {
        $transaction = Transaction::factory()->create();

        $this->assertNotNull($transaction->created_at);
    }

    public function test_updated_at_is_not_used(): void
    {
        $transaction = Transaction::factory()->create();

        $this->assertNull(Transaction::UPDATED_AT);
        $this->assertArrayNotHasKey('updated_at', $transaction->getAttributes());
    }
}
