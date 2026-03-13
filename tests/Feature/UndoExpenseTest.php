<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UndoExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_destroy_expense_removes_transaction(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Groceries',
            'monthly_target' => 50000,
            'priority_order' => 1,
        ]);

        $expense = Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'amount' => -4500,
            'type' => Transaction::TYPE_EXPENSE,
            'description' => 'Weekly groceries',
        ]);

        $response = $this->delete(route('expenses.destroy', $expense));

        $response->assertRedirect(route('buckets.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('transactions', ['id' => $expense->id]);
    }

    public function test_destroy_expense_restores_bucket_balance(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Groceries',
            'monthly_target' => 50000,
            'priority_order' => 1,
        ]);

        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $expense = Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'amount' => -4500,
            'type' => Transaction::TYPE_EXPENSE,
        ]);

        $this->assertEquals(45500, $bucket->balance);

        $this->delete(route('expenses.destroy', $expense));

        $bucket->refresh();
        $this->assertEquals(50000, $bucket->balance);
    }

    public function test_destroy_expense_only_removes_expense_type(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Groceries',
            'monthly_target' => 50000,
            'priority_order' => 1,
        ]);

        $allocation = Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $response = $this->delete(route('expenses.destroy', $allocation));

        $response->assertNotFound();
        $this->assertDatabaseHas('transactions', ['id' => $allocation->id]);
    }

    public function test_destroy_nonexistent_expense_returns_404(): void
    {
        $response = $this->delete(route('expenses.destroy', 99999));

        $response->assertNotFound();
    }
}
