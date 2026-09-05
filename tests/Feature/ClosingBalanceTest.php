<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Period;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClosingBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_buckets_page_shows_current_estimated_balance(): void
    {
        $bucket1 = Bucket::factory()->create(['type' => 'fixed', 'monthly_target' => 50000]);
        $bucket2 = Bucket::factory()->create(['type' => 'fixed', 'monthly_target' => 30000]);

        Transaction::factory()->create([
            'bucket_id' => $bucket1->id,
            'amount' => 25000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);
        Transaction::factory()->create([
            'bucket_id' => $bucket2->id,
            'amount' => 15000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $response = $this->get(route('buckets.index'));

        $response->assertOk();
        $response->assertSee('Current Estimated Balance');
        $response->assertSee('$400.00');
    }

    public function test_sweep_stores_closing_balance_on_period(): void
    {
        $savings = Bucket::factory()->primarySavings()->create();
        $bucket = Bucket::factory()->create([
            'type' => 'fixed',
            'monthly_target' => 50000,
            'sweeps_excess' => true,
        ]);

        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'amount' => 30000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);
        Transaction::factory()->create([
            'bucket_id' => $savings->id,
            'amount' => 10000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        Period::create(['month' => now()->startOfMonth(), 'closed_at' => null]);

        $this->post(route('sweep.store'), ['month' => now()->format('Y-m')]);

        $period = Period::where('month', now()->startOfMonth())->first();
        $this->assertNotNull($period->closed_at);
        // Total balance after sweep: savings got 10000 + 30000 swept in = 40000, bucket went to 0
        $this->assertEquals(40000, $period->closing_balance);
    }

    public function test_history_page_shows_closing_balance(): void
    {
        Period::create([
            'month' => Carbon::create(2026, 2, 1),
            'closed_at' => now(),
            'closing_balance' => 150000,
        ]);

        $response = $this->get(route('history.index'));

        $response->assertOk();
        $response->assertSee('$1,500.00');
    }
}
