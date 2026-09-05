<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function seedActivity(): void
    {
        $rent = Bucket::factory()->fixed()->create(['name' => 'Rent']);
        $savings = Bucket::factory()->primarySavings()->create(['name' => 'Savings']);

        $deposit = Deposit::factory()->create([
            'deposit_date' => Carbon::now()->subDays(4),
            'amount' => 100000,
            'description' => 'Paycheck one',
        ]);
        Transaction::factory()->create([
            'bucket_id' => $rent->id,
            'deposit_id' => $deposit->id,
            'amount' => 100000,
            'type' => Transaction::TYPE_ALLOCATION,
            'created_at' => Carbon::now()->subDays(4),
        ]);

        Transaction::factory()->create([
            'deposit_id' => null,
            'bucket_id' => $rent->id,
            'amount' => -4500,
            'type' => Transaction::TYPE_EXPENSE,
            'description' => 'Water bill',
            'created_at' => Carbon::now()->subDays(3),
        ]);

        $reference = Str::uuid()->toString();
        Transaction::factory()->create([
            'deposit_id' => null,
            'bucket_id' => $rent->id,
            'amount' => -20000,
            'type' => Transaction::TYPE_TRANSFER,
            'reference_id' => $reference,
            'description' => 'Car repair',
            'created_at' => Carbon::now()->subDays(2),
        ]);
        Transaction::factory()->create([
            'deposit_id' => null,
            'bucket_id' => $savings->id,
            'amount' => 20000,
            'type' => Transaction::TYPE_TRANSFER,
            'reference_id' => $reference,
            'description' => 'Car repair',
            'created_at' => Carbon::now()->subDays(2),
        ]);
    }

    public function test_activity_page_shows_all_kinds_by_default(): void
    {
        $this->seedActivity();

        $response = $this->get(route('deposits.index'));

        $response->assertOk();
        $response->assertViewIs('deposits.index');
        $response->assertViewHas('activityType', 'all');
        $response->assertViewHas('entries');
        $response->assertSee('Paycheck one');
        $response->assertSee('Water bill');
        $response->assertSee('Car repair');
    }

    public function test_activity_page_filters_to_expenses(): void
    {
        $this->seedActivity();

        $response = $this->get(route('deposits.index', ['type' => 'expenses']));

        $response->assertOk();
        $response->assertViewHas('activityType', 'expenses');
        $response->assertSee('Water bill');
        $response->assertDontSee('Paycheck one');
        $response->assertDontSee('Car repair');
    }

    public function test_activity_page_filters_to_transfers(): void
    {
        $this->seedActivity();

        $response = $this->get(route('deposits.index', ['type' => 'transfers']));

        $response->assertViewHas('activityType', 'transfers');
        $response->assertSee('Car repair');
        $response->assertDontSee('Water bill');
    }

    public function test_activity_page_falls_back_to_all_for_unknown_type(): void
    {
        $this->seedActivity();

        $response = $this->get(route('deposits.index', ['type' => 'nonsense']));

        $response->assertOk();
        $response->assertViewHas('activityType', 'all');
    }

    public function test_activity_page_still_allows_undoing_a_deposit(): void
    {
        $this->seedActivity();

        $response = $this->get(route('deposits.index', ['type' => 'deposits']));

        $deposit = Deposit::first();
        $response->assertSee(route('deposits.destroy', $deposit));
    }

    public function test_activity_page_allows_undoing_an_expense(): void
    {
        $this->seedActivity();

        $expense = Transaction::where('type', Transaction::TYPE_EXPENSE)->first();

        $response = $this->get(route('deposits.index', ['type' => 'expenses']));

        $response->assertSee(route('expenses.destroy', $expense));
    }

    public function test_activity_page_shows_empty_state(): void
    {
        $response = $this->get(route('deposits.index'));

        $response->assertOk();
        $response->assertSee('No activity yet');
    }

    public function test_dashboard_shows_recent_activity(): void
    {
        $this->seedActivity();

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('recentActivity');
        $response->assertSee('Recent Activity');
        $response->assertSee('Water bill');
    }
}
