<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bucket_monthly_balance_sums_only_current_month_transactions(): void
    {
        Carbon::setTestNow('2026-04-15');

        $bucket = Bucket::factory()->fixed()->create([
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        // Current month allocation
        $deposit = Deposit::factory()->create(['deposit_date' => '2026-04-01', 'amount' => 100000]);
        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $deposit->id,
            'amount' => 100000,
            'type' => Transaction::TYPE_ALLOCATION,
            'created_at' => '2026-04-01',
        ]);

        // Current month expense
        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => null,
            'amount' => -30000,
            'type' => Transaction::TYPE_EXPENSE,
            'created_at' => '2026-04-10',
        ]);

        // Last month transaction (should NOT count)
        $oldDeposit = Deposit::factory()->create(['deposit_date' => '2026-03-01', 'amount' => 50000]);
        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $oldDeposit->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
            'created_at' => '2026-03-01',
        ]);

        // Monthly balance = 100000 - 30000 = 70000 (only April)
        $this->assertEquals(70000, $bucket->monthlyBalance());

        // Total balance = 100000 - 30000 + 50000 = 120000 (all time)
        $this->assertEquals(120000, $bucket->balance);

        Carbon::setTestNow();
    }

    public function test_bucket_monthly_balance_excludes_sweep_transactions(): void
    {
        Carbon::setTestNow('2026-04-15');

        $bucket = Bucket::factory()->fixed()->create([
            'monthly_target' => 50000,
            'priority_order' => 1,
            'sweeps_excess' => true,
        ]);

        // Current month allocation
        $deposit = Deposit::factory()->create(['deposit_date' => '2026-04-01', 'amount' => 50000]);
        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $deposit->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
            'created_at' => '2026-04-01',
        ]);

        // Sweep transaction (should NOT count toward monthly balance)
        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => null,
            'amount' => -50000,
            'type' => Transaction::TYPE_SWEEP,
            'created_at' => '2026-04-15',
        ]);

        // Monthly balance should be 50000 (sweep excluded)
        $this->assertEquals(50000, $bucket->monthlyBalance());

        // Total balance includes sweep = 0
        $this->assertEquals(0, $bucket->balance);

        Carbon::setTestNow();
    }

    public function test_bucket_monthly_balance_with_no_transactions_is_zero(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        $this->assertEquals(0, $bucket->monthlyBalance());
    }

    public function test_buckets_index_shows_monthly_balance_and_total_balance(): void
    {
        Carbon::setTestNow('2026-04-15');

        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        // April allocation
        $deposit = Deposit::factory()->create(['deposit_date' => '2026-04-01', 'amount' => 100000]);
        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $deposit->id,
            'amount' => 100000,
            'type' => Transaction::TYPE_ALLOCATION,
            'created_at' => '2026-04-01',
        ]);

        // March carryover (part of total balance only)
        $oldDeposit = Deposit::factory()->create(['deposit_date' => '2026-03-01', 'amount' => 50000]);
        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $oldDeposit->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
            'created_at' => '2026-03-01',
        ]);

        $response = $this->get(route('buckets.index'));

        $response->assertOk();
        // Should show monthly balance ($1,000.00) and total balance ($1,500.00)
        $response->assertSee('Monthly Bal');
        $response->assertSee('Total Bal');

        Carbon::setTestNow();
    }
}
