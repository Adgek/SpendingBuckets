<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_deposit_creates_deposit_and_runs_allocation(): void
    {
        Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);
        Bucket::factory()->primarySavings()->create([
            'name' => 'Savings',
            'excess_percentage' => 100,
        ]);

        $response = $this->postJson('/api/deposits', [
            'amount' => 150000,
            'deposit_date' => '2026-03-01',
            'description' => 'March paycheck',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['amount' => 150000]);

        $this->assertDatabaseHas('deposits', [
            'amount' => 150000,
            'description' => 'March paycheck',
        ]);

        // Allocation should have run: 100000 to Rent, 50000 to Savings
        $this->assertDatabaseHas('transactions', [
            'bucket_id' => Bucket::where('name', 'Rent')->first()->id,
            'amount' => 100000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);
        $this->assertDatabaseHas('transactions', [
            'bucket_id' => Bucket::where('name', 'Savings')->first()->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);
    }

    public function test_store_deposit_validates_required_fields(): void
    {
        $response = $this->postJson('/api/deposits', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount', 'deposit_date']);
    }

    public function test_store_deposit_validates_amount_is_positive_integer(): void
    {
        $response = $this->postJson('/api/deposits', [
            'amount' => -100,
            'deposit_date' => '2026-03-01',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    public function test_store_deposit_validates_deposit_date_is_valid_date(): void
    {
        $response = $this->postJson('/api/deposits', [
            'amount' => 100000,
            'deposit_date' => 'not-a-date',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['deposit_date']);
    }

    public function test_index_returns_all_deposits(): void
    {
        Deposit::factory()->count(3)->create();

        $response = $this->getJson('/api/deposits');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }
}
