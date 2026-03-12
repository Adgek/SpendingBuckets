<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_expense_creates_negative_transaction(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Groceries',
            'monthly_target' => 50000,
            'priority_order' => 1,
        ]);

        // Give the bucket some funds first
        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $response = $this->postJson('/api/expenses', [
            'bucket_id' => $bucket->id,
            'amount' => 4500,
            'description' => 'Weekly groceries',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('transactions', [
            'bucket_id' => $bucket->id,
            'amount' => -4500,
            'type' => Transaction::TYPE_EXPENSE,
            'description' => 'Weekly groceries',
        ]);
    }

    public function test_store_expense_validates_required_fields(): void
    {
        $response = $this->postJson('/api/expenses', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bucket_id', 'amount']);
    }

    public function test_store_expense_validates_bucket_exists(): void
    {
        $response = $this->postJson('/api/expenses', [
            'bucket_id' => 9999,
            'amount' => 1000,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bucket_id']);
    }

    public function test_store_expense_validates_amount_is_positive(): void
    {
        $bucket = Bucket::factory()->create();

        $response = $this->postJson('/api/expenses', [
            'bucket_id' => $bucket->id,
            'amount' => -500,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    public function test_store_expense_returns_transaction_with_bucket(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Water Bill',
            'monthly_target' => 5000,
            'priority_order' => 1,
        ]);

        $response = $this->postJson('/api/expenses', [
            'bucket_id' => $bucket->id,
            'amount' => 4500,
            'description' => 'Paid water bill',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['type' => Transaction::TYPE_EXPENSE]);
        $response->assertJsonFragment(['amount' => -4500]);
    }
}
