<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UndoDepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_destroy_deposit_removes_deposit_and_its_transactions(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        $deposit = Deposit::factory()->create(['amount' => 100000]);

        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $deposit->id,
            'amount' => 100000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $response = $this->delete(route('deposits.destroy', $deposit));

        $response->assertRedirect(route('deposits.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('deposits', ['id' => $deposit->id]);
        $this->assertDatabaseMissing('transactions', ['deposit_id' => $deposit->id]);
    }

    public function test_destroy_deposit_restores_bucket_balance(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        $deposit = Deposit::factory()->create(['amount' => 50000]);

        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $deposit->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $this->assertEquals(50000, $bucket->balance);

        $this->delete(route('deposits.destroy', $deposit));

        $bucket->refresh();
        $this->assertEquals(0, $bucket->balance);
    }

    public function test_destroy_deposit_with_multiple_allocation_transactions(): void
    {
        $bucketA = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);
        $bucketB = Bucket::factory()->fixed()->create([
            'name' => 'Water',
            'monthly_target' => 5000,
            'priority_order' => 2,
        ]);

        $deposit = Deposit::factory()->create(['amount' => 105000]);

        Transaction::factory()->create([
            'bucket_id' => $bucketA->id,
            'deposit_id' => $deposit->id,
            'amount' => 100000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);
        Transaction::factory()->create([
            'bucket_id' => $bucketB->id,
            'deposit_id' => $deposit->id,
            'amount' => 5000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $this->delete(route('deposits.destroy', $deposit));

        $this->assertDatabaseMissing('deposits', ['id' => $deposit->id]);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_destroy_nonexistent_deposit_returns_404(): void
    {
        $response = $this->delete(route('deposits.destroy', 99999));

        $response->assertNotFound();
    }
}
