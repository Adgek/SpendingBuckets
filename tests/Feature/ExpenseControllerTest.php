<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Period;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
        Carbon::setTestNow('2026-04-20 12:00:00');

        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Groceries',
            'monthly_target' => 50000,
            'priority_order' => 1,
        ]);

        // Pin the funding deposit so the active period is April 2026 rather than
        // whatever random date the deposit factory would otherwise invent.
        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
            'deposit_id' => Deposit::factory()->create(['deposit_date' => '2026-04-01'])->id,
        ]);

        $response = $this->post(route('expenses.store'), [
            'bucket_id' => $bucket->id,
            'amount' => '45.00',
            'description' => 'Weekly groceries',
            'expense_date' => '2026-04-15',
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

    public function test_store_expense_uses_provided_date_for_created_at(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Groceries',
            'monthly_target' => 50000,
            'priority_order' => 1,
        ]);

        $this->post(route('expenses.store'), [
            'bucket_id' => $bucket->id,
            'amount' => '12.34',
            'expense_date' => '2026-04-10',
        ]);

        $transaction = Transaction::where('type', Transaction::TYPE_EXPENSE)->firstOrFail();

        $this->assertSame('2026-04-10', $transaction->created_at->toDateString());
    }

    public function test_store_expense_validates_required_fields(): void
    {
        $response = $this->post(route('expenses.store'), []);

        $response->assertSessionHasErrors(['bucket_id', 'amount', 'expense_date']);
    }

    public function test_store_expense_validates_date_is_not_in_future(): void
    {
        $bucket = Bucket::factory()->create();

        Carbon::setTestNow('2026-04-15 12:00:00');

        $response = $this->post(route('expenses.store'), [
            'bucket_id' => $bucket->id,
            'amount' => '10.00',
            'expense_date' => '2026-04-20',
        ]);

        $response->assertSessionHasErrors(['expense_date']);

        Carbon::setTestNow();
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

    public function test_expense_cannot_be_dated_past_the_unswept_active_period(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');
        Period::create(['month' => '2026-07-01']);

        $bucket = Bucket::factory()->fixed()->create(['name' => 'Groceries', 'priority_order' => 1]);

        $response = $this->post(route('expenses.store'), [
            'bucket_id' => $bucket->id,
            'amount' => '45.00',
            'expense_date' => '2026-08-10',
        ]);

        $response->assertSessionHasErrors(['expense_date']);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_expense_in_the_new_month_is_allowed_once_the_period_is_swept(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');
        Period::create(['month' => '2026-07-01', 'closed_at' => '2026-08-25 11:00:00']);

        $bucket = Bucket::factory()->fixed()->create(['name' => 'Groceries', 'priority_order' => 1]);

        $response = $this->post(route('expenses.store'), [
            'bucket_id' => $bucket->id,
            'amount' => '45.00',
            'expense_date' => '2026-08-10',
        ]);

        $response->assertRedirect(route('buckets.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_expense_within_the_active_period_is_still_allowed(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');
        Period::create(['month' => '2026-07-01']);

        $bucket = Bucket::factory()->fixed()->create(['name' => 'Groceries', 'priority_order' => 1]);

        $response = $this->post(route('expenses.store'), [
            'bucket_id' => $bucket->id,
            'amount' => '45.00',
            'expense_date' => '2026-07-14',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('transactions', 1);
    }
}
