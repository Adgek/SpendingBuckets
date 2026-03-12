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

        $response = $this->get(route('expenses.create'));

        $response->assertOk();
        $response->assertViewIs('expenses.create');
        $response->assertViewHas('buckets');
    }

    public function test_store_expense_converts_dollars_to_cents_and_redirects(): void
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

        $response = $this->post(route('expenses.store'), [
            'bucket_id' => $bucket->id,
            'amount' => '45.00',
            'description' => 'Weekly groceries',
        ]);

        $response->assertRedirect(route('buckets.index'));
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
        $response = $this->post(route('expenses.store'), []);

        $response->assertSessionHasErrors(['bucket_id', 'amount']);
    }

    public function test_store_expense_validates_bucket_exists(): void
    {
        $response = $this->post(route('expenses.store'), [
            'bucket_id' => 9999,
            'amount' => '10.00',
        ]);

        $response->assertSessionHasErrors(['bucket_id']);
    }

    public function test_store_expense_validates_amount_is_positive(): void
    {
        $bucket = Bucket::factory()->create();

        $response = $this->post(route('expenses.store'), [
            'bucket_id' => $bucket->id,
            'amount' => '-5.00',
        ]);

        $response->assertSessionHasErrors(['amount']);
    }
}
