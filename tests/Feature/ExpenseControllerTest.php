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

    public function test_create_displays_expense_form_with_buckets(): void
    {
        Bucket::factory()->fixed()->create(['name' => 'Groceries', 'priority_order' => 1]);

        $response = $this->get('/expenses/create');

        $response->assertOk();
        $response->assertViewIs('expenses.create');
        $response->assertViewHas('buckets');
    }

    public function test_store_expense_creates_negative_transaction_and_redirects(): void
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

        $response = $this->post('/expenses', [
            'bucket_id' => $bucket->id,
            'amount' => 4500,
            'description' => 'Weekly groceries',
        ]);

        $response->assertRedirect('/buckets');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'bucket_id' => $bucket->id,
            'amount' => -4500,
            'type' => Transaction::TYPE_EXPENSE,
            'description' => 'Weekly groceries',
        ]);
    }

    public function test_store_expense_validates_required_fields(): void
    {
        $response = $this->post('/expenses', []);

        $response->assertSessionHasErrors(['bucket_id', 'amount']);
    }

    public function test_store_expense_validates_bucket_exists(): void
    {
        $response = $this->post('/expenses', [
            'bucket_id' => 9999,
            'amount' => 1000,
        ]);

        $response->assertSessionHasErrors(['bucket_id']);
    }

    public function test_store_expense_validates_amount_is_positive(): void
    {
        $bucket = Bucket::factory()->create();

        $response = $this->post('/expenses', [
            'bucket_id' => $bucket->id,
            'amount' => -500,
        ]);

        $response->assertSessionHasErrors(['amount']);
    }
}
