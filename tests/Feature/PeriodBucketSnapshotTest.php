<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RunSweepAction;
use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\PeriodBucketSnapshot;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodBucketSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_sweep_creates_snapshots_for_all_buckets(): void
    {
        Carbon::setTestNow('2026-04-30');

        $savings = Bucket::factory()->primarySavings()->create([
            'name' => 'Savings',
            'excess_percentage' => 100,
        ]);

        $rent = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
            'sweeps_excess' => false,
        ]);

        $groceries = Bucket::factory()->fixed()->create([
            'name' => 'Groceries',
            'monthly_target' => 50000,
            'priority_order' => 2,
            'sweeps_excess' => true,
        ]);

        // Allocation for April
        $deposit = Deposit::factory()->create(['deposit_date' => '2026-04-01', 'amount' => 180000]);
        Transaction::factory()->create([
            'bucket_id' => $rent->id,
            'deposit_id' => $deposit->id,
            'amount' => 100000,
            'type' => Transaction::TYPE_ALLOCATION,
            'created_at' => '2026-04-01',
        ]);
        Transaction::factory()->create([
            'bucket_id' => $groceries->id,
            'deposit_id' => $deposit->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
            'created_at' => '2026-04-01',
        ]);
        Transaction::factory()->create([
            'bucket_id' => $savings->id,
            'deposit_id' => $deposit->id,
            'amount' => 30000,
            'type' => Transaction::TYPE_ALLOCATION,
            'created_at' => '2026-04-01',
        ]);

        // Expense on groceries
        Transaction::factory()->create([
            'bucket_id' => $groceries->id,
            'deposit_id' => null,
            'amount' => -20000,
            'type' => Transaction::TYPE_EXPENSE,
            'created_at' => '2026-04-15',
        ]);

        $action = new RunSweepAction();
        $action->execute('2026-04');

        // Should have snapshots for all 3 buckets
        $this->assertEquals(3, PeriodBucketSnapshot::count());

        // Check groceries snapshot: target=50000, funded=50000, paid=20000, swept=30000
        $grocerySnapshot = PeriodBucketSnapshot::where('bucket_id', $groceries->id)->first();
        $this->assertNotNull($grocerySnapshot);
        $this->assertEquals(50000, $grocerySnapshot->monthly_target);
        $this->assertEquals(50000, $grocerySnapshot->funded);
        $this->assertEquals(20000, $grocerySnapshot->paid);
        $this->assertEquals(30000, $grocerySnapshot->swept);

        // Rent snapshot: target=100000, funded=100000, paid=0, swept=0 (doesn't sweep)
        $rentSnapshot = PeriodBucketSnapshot::where('bucket_id', $rent->id)->first();
        $this->assertNotNull($rentSnapshot);
        $this->assertEquals(100000, $rentSnapshot->monthly_target);
        $this->assertEquals(100000, $rentSnapshot->funded);
        $this->assertEquals(0, $rentSnapshot->paid);
        $this->assertEquals(0, $rentSnapshot->swept);

        Carbon::setTestNow();
    }

    public function test_history_shows_per_bucket_breakdown(): void
    {
        Carbon::setTestNow('2026-05-01');

        $savings = Bucket::factory()->primarySavings()->create([
            'name' => 'Savings',
            'excess_percentage' => 100,
        ]);

        $rent = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
            'sweeps_excess' => false,
        ]);

        // Create April data and sweep it
        $deposit = Deposit::factory()->create(['deposit_date' => '2026-04-01', 'amount' => 100000]);
        Transaction::factory()->create([
            'bucket_id' => $rent->id,
            'deposit_id' => $deposit->id,
            'amount' => 100000,
            'type' => Transaction::TYPE_ALLOCATION,
            'created_at' => '2026-04-01',
        ]);
        Transaction::factory()->create([
            'bucket_id' => $rent->id,
            'deposit_id' => null,
            'amount' => -80000,
            'type' => Transaction::TYPE_EXPENSE,
            'created_at' => '2026-04-15',
        ]);

        $action = new RunSweepAction();
        $action->execute('2026-04');

        $response = $this->get(route('history.index'));

        $response->assertOk();
        $response->assertSee('April 2026');
        // Should show the bucket name and stats
        $response->assertSee('Rent');
        $response->assertSee('Target');
        $response->assertSee('Funded');
        $response->assertSee('Paid');
        $response->assertSee('Swept');

        Carbon::setTestNow();
    }
}
