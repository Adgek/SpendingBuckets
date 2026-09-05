<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Period;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivePeriodIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_active_period_not_calendar_month(): void
    {
        $savings = Bucket::factory()->primarySavings()->create();
        $bucket = Bucket::factory()->create([
            'type' => 'fixed',
            'monthly_target' => 50000,
            'sweeps_excess' => true,
        ]);

        // A deposit in March
        $deposit = Deposit::factory()->create([
            'amount' => 50000,
            'deposit_date' => Carbon::create(2026, 3, 15),
        ]);
        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $deposit->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        // March period exists but not closed
        Period::create(['month' => Carbon::create(2026, 3, 1), 'closed_at' => null]);

        // It's now April 1st, but March hasn't been closed
        Carbon::setTestNow(Carbon::create(2026, 4, 1));

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('March 2026');
        $response->assertSee('$500.00');
    }

    public function test_dashboard_advances_to_april_after_march_is_closed(): void
    {
        $savings = Bucket::factory()->primarySavings()->create();

        // March is closed
        Period::create(['month' => Carbon::create(2026, 3, 1), 'closed_at' => Carbon::create(2026, 3, 31)]);

        Carbon::setTestNow(Carbon::create(2026, 4, 1));

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('April 2026');
    }

    public function test_sweep_page_shows_active_period_month(): void
    {
        $savings = Bucket::factory()->primarySavings()->create();

        // March period exists but unclosed
        Period::create(['month' => Carbon::create(2026, 3, 1), 'closed_at' => null]);

        Carbon::setTestNow(Carbon::create(2026, 4, 1));

        $response = $this->get(route('sweep.create'));

        $response->assertOk();
        $response->assertSee('2026-03');
    }

    public function test_sweep_closes_the_active_period(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 31));

        $savings = Bucket::factory()->primarySavings()->create();
        $bucket = Bucket::factory()->create([
            'type' => 'fixed',
            'monthly_target' => 50000,
            'sweeps_excess' => true,
        ]);

        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'amount' => 20000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        // March period not closed
        Period::create(['month' => Carbon::create(2026, 3, 1), 'closed_at' => null]);

        $response = $this->post(route('sweep.store'), ['month' => '2026-03']);

        $response->assertRedirect(route('buckets.index'));

        // Period should now be closed
        $period = Period::where('month', Carbon::create(2026, 3, 1))->first();
        $this->assertNotNull($period);
        $this->assertNotNull($period->closed_at);
    }

    public function test_history_page_loads(): void
    {
        $response = $this->get(route('history.index'));

        $response->assertOk();
    }

    public function test_history_page_lists_closed_months(): void
    {
        Period::create(['month' => Carbon::create(2026, 1, 1), 'closed_at' => now()]);
        Period::create(['month' => Carbon::create(2026, 2, 1), 'closed_at' => now()]);
        Period::create(['month' => Carbon::create(2026, 3, 1), 'closed_at' => null]);

        $response = $this->get(route('history.index'));

        $response->assertOk();
        $response->assertSee('January 2026');
        $response->assertSee('February 2026');
        $response->assertDontSee('March 2026');
    }

    public function test_dashboard_can_show_specific_previous_month(): void
    {
        $bucket = Bucket::factory()->create([
            'type' => 'fixed',
            'monthly_target' => 50000,
        ]);

        Period::create(['month' => Carbon::create(2026, 2, 1), 'closed_at' => now()]);

        $deposit = Deposit::factory()->create([
            'amount' => 40000,
            'deposit_date' => Carbon::create(2026, 2, 15),
        ]);
        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $deposit->id,
            'amount' => 40000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 4, 1));

        $response = $this->get(route('dashboard', ['month' => '2026-02']));

        $response->assertOk();
        $response->assertSee('February 2026');
        $response->assertSee('$400.00');
    }
}
