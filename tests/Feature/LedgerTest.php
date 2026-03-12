<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LedgerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------
    // Inflow (Allocation) Tests
    // -------------------------------------------------------

    public function test_allocation_increases_bucket_balance(): void
    {
        $bucket = Bucket::factory()->fixed()->create();
        $deposit = Deposit::factory()->create(['amount' => 200000]);

        Transaction::create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $deposit->id,
            'amount' => 150000,
            'type' => Transaction::TYPE_ALLOCATION,
            'description' => 'Paycheck allocation',
        ]);

        $this->assertEquals(150000, $bucket->balance);
    }

    public function test_multiple_allocations_stack_correctly(): void
    {
        $bucket = Bucket::factory()->fixed()->create(['monthly_target' => 200000]);
        $deposit1 = Deposit::factory()->create(['amount' => 100000]);
        $deposit2 = Deposit::factory()->create(['amount' => 100000]);

        Transaction::create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $deposit1->id,
            'amount' => 100000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        Transaction::create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $deposit2->id,
            'amount' => 100000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $this->assertEquals(200000, $bucket->balance);
    }

    // -------------------------------------------------------
    // Outflow (Expense) Tests
    // -------------------------------------------------------

    public function test_expense_decreases_bucket_balance(): void
    {
        $bucket = Bucket::factory()->fixed()->create();

        Transaction::create([
            'bucket_id' => $bucket->id,
            'amount' => 100000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        Transaction::create([
            'bucket_id' => $bucket->id,
            'amount' => -45000,
            'type' => Transaction::TYPE_EXPENSE,
            'description' => 'Water bill',
        ]);

        $this->assertEquals(55000, $bucket->balance);
    }

    public function test_multiple_expenses_reduce_balance_correctly(): void
    {
        $bucket = Bucket::factory()->fixed()->create();

        Transaction::create([
            'bucket_id' => $bucket->id,
            'amount' => 100000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        Transaction::create([
            'bucket_id' => $bucket->id,
            'amount' => -25000,
            'type' => Transaction::TYPE_EXPENSE,
            'description' => 'Groceries run 1',
        ]);

        Transaction::create([
            'bucket_id' => $bucket->id,
            'amount' => -30000,
            'type' => Transaction::TYPE_EXPENSE,
            'description' => 'Groceries run 2',
        ]);

        $this->assertEquals(45000, $bucket->balance);
    }

    public function test_expense_can_make_balance_negative(): void
    {
        $bucket = Bucket::factory()->fixed()->create();

        Transaction::create([
            'bucket_id' => $bucket->id,
            'amount' => 10000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        Transaction::create([
            'bucket_id' => $bucket->id,
            'amount' => -25000,
            'type' => Transaction::TYPE_EXPENSE,
            'description' => 'Overspend',
        ]);

        $this->assertEquals(-15000, $bucket->balance);
    }

    // -------------------------------------------------------
    // Transfer Tests (Between Buckets)
    // -------------------------------------------------------

    public function test_transfer_moves_money_between_buckets_atomically(): void
    {
        $source = Bucket::factory()->fixed()->create(['name' => 'Emergency Fund']);
        $destination = Bucket::factory()->fixed()->create(['name' => 'Car Repair']);

        // Seed source bucket
        Transaction::create([
            'bucket_id' => $source->id,
            'amount' => 500000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $referenceId = Str::uuid()->toString();

        DB::transaction(function () use ($source, $destination, $referenceId) {
            Transaction::create([
                'bucket_id' => $source->id,
                'amount' => -100000,
                'type' => Transaction::TYPE_TRANSFER,
                'reference_id' => $referenceId,
                'description' => 'Transfer to Car Repair',
            ]);

            Transaction::create([
                'bucket_id' => $destination->id,
                'amount' => 100000,
                'type' => Transaction::TYPE_TRANSFER,
                'reference_id' => $referenceId,
                'description' => 'Transfer from Emergency Fund',
            ]);
        });

        $this->assertEquals(400000, $source->balance);
        $this->assertEquals(100000, $destination->balance);
    }

    public function test_transfer_pair_shares_reference_id_and_balances_to_zero(): void
    {
        $source = Bucket::factory()->fixed()->create();
        $destination = Bucket::factory()->fixed()->create();

        $referenceId = Str::uuid()->toString();

        DB::transaction(function () use ($source, $destination, $referenceId) {
            Transaction::create([
                'bucket_id' => $source->id,
                'amount' => -50000,
                'type' => Transaction::TYPE_TRANSFER,
                'reference_id' => $referenceId,
            ]);

            Transaction::create([
                'bucket_id' => $destination->id,
                'amount' => 50000,
                'type' => Transaction::TYPE_TRANSFER,
                'reference_id' => $referenceId,
            ]);
        });

        $linkedTransactions = Transaction::where('reference_id', $referenceId)->get();

        $this->assertCount(2, $linkedTransactions);
        $this->assertEquals(0, $linkedTransactions->sum('amount'));
    }

    public function test_transfer_is_zero_sum_across_system(): void
    {
        $bucketA = Bucket::factory()->fixed()->create();
        $bucketB = Bucket::factory()->fixed()->create();

        // Seed both buckets
        Transaction::create(['bucket_id' => $bucketA->id, 'amount' => 200000, 'type' => Transaction::TYPE_ALLOCATION]);
        Transaction::create(['bucket_id' => $bucketB->id, 'amount' => 100000, 'type' => Transaction::TYPE_ALLOCATION]);

        $referenceId = Str::uuid()->toString();

        DB::transaction(function () use ($bucketA, $bucketB, $referenceId) {
            Transaction::create([
                'bucket_id' => $bucketA->id,
                'amount' => -75000,
                'type' => Transaction::TYPE_TRANSFER,
                'reference_id' => $referenceId,
            ]);

            Transaction::create([
                'bucket_id' => $bucketB->id,
                'amount' => 75000,
                'type' => Transaction::TYPE_TRANSFER,
                'reference_id' => $referenceId,
            ]);
        });

        $totalSystem = Transaction::sum('amount');
        $this->assertEquals(300000, $totalSystem); // Original 200k + 100k, transfers net zero
    }

    // -------------------------------------------------------
    // Sweep Tests
    // -------------------------------------------------------

    public function test_sweep_zeroes_bucket_and_moves_to_savings_atomically(): void
    {
        $groceries = Bucket::factory()->fixed()->create([
            'name' => 'Groceries',
            'sweeps_excess' => true,
        ]);
        $savings = Bucket::factory()->excess()->create(['name' => 'Savings']);

        // Fund groceries
        Transaction::create(['bucket_id' => $groceries->id, 'amount' => 50000, 'type' => Transaction::TYPE_ALLOCATION]);
        // Spend some
        Transaction::create(['bucket_id' => $groceries->id, 'amount' => -35000, 'type' => Transaction::TYPE_EXPENSE]);

        // Remaining = 15000, sweep it
        $remaining = $groceries->balance;
        $this->assertEquals(15000, $remaining);

        $referenceId = Str::uuid()->toString();

        DB::transaction(function () use ($groceries, $savings, $remaining, $referenceId) {
            Transaction::create([
                'bucket_id' => $groceries->id,
                'amount' => -$remaining,
                'type' => Transaction::TYPE_SWEEP,
                'reference_id' => $referenceId,
                'description' => 'End-of-month sweep',
            ]);

            Transaction::create([
                'bucket_id' => $savings->id,
                'amount' => $remaining,
                'type' => Transaction::TYPE_SWEEP,
                'reference_id' => $referenceId,
                'description' => 'Sweep from Groceries',
            ]);
        });

        $this->assertEquals(0, $groceries->balance);
        $this->assertEquals(15000, $savings->balance);
    }

    public function test_sweep_skips_bucket_with_sweeps_excess_false(): void
    {
        $emergencyFund = Bucket::factory()->fixed()->create([
            'name' => 'Emergency Fund',
            'sweeps_excess' => false,
        ]);

        Transaction::create(['bucket_id' => $emergencyFund->id, 'amount' => 200000, 'type' => Transaction::TYPE_ALLOCATION]);

        // Simulate end-of-month: only sweep buckets where sweeps_excess = true
        $sweepable = Bucket::where('sweeps_excess', true)->get();

        $this->assertTrue($sweepable->isEmpty());
        $this->assertEquals(200000, $emergencyFund->balance);
    }

    public function test_sweep_is_noop_when_bucket_balance_is_zero(): void
    {
        $groceries = Bucket::factory()->fixed()->create([
            'name' => 'Groceries',
            'sweeps_excess' => true,
        ]);
        $savings = Bucket::factory()->excess()->create(['name' => 'Savings']);

        // Fund and fully spend
        Transaction::create(['bucket_id' => $groceries->id, 'amount' => 50000, 'type' => Transaction::TYPE_ALLOCATION]);
        Transaction::create(['bucket_id' => $groceries->id, 'amount' => -50000, 'type' => Transaction::TYPE_EXPENSE]);

        $this->assertEquals(0, $groceries->balance);

        // Sweep logic: only create transactions if balance > 0
        $remaining = $groceries->balance;
        if ($remaining > 0) {
            $this->fail('Should not reach here — balance is zero');
        }

        // No sweep transactions should exist
        $this->assertEquals(0, Transaction::where('type', Transaction::TYPE_SWEEP)->count());
        $this->assertEquals(0, $groceries->balance);
        $this->assertEquals(0, $savings->balance);
    }

    // -------------------------------------------------------
    // Mixed Operations / Accuracy Tests
    // -------------------------------------------------------

    public function test_complex_ledger_scenario_maintains_accuracy(): void
    {
        $rent = Bucket::factory()->fixed()->create(['name' => 'Rent', 'monthly_target' => 150000, 'priority_order' => 1]);
        $utilities = Bucket::factory()->fixed()->create(['name' => 'Utilities', 'monthly_target' => 50000, 'priority_order' => 2]);
        $savings = Bucket::factory()->excess()->create(['name' => 'Savings']);

        $deposit = Deposit::factory()->create(['amount' => 250000]);

        // Allocations from deposit
        Transaction::create(['bucket_id' => $rent->id, 'deposit_id' => $deposit->id, 'amount' => 150000, 'type' => Transaction::TYPE_ALLOCATION]);
        Transaction::create(['bucket_id' => $utilities->id, 'deposit_id' => $deposit->id, 'amount' => 50000, 'type' => Transaction::TYPE_ALLOCATION]);
        Transaction::create(['bucket_id' => $savings->id, 'deposit_id' => $deposit->id, 'amount' => 50000, 'type' => Transaction::TYPE_ALLOCATION]);

        // Pay rent
        Transaction::create(['bucket_id' => $rent->id, 'amount' => -150000, 'type' => Transaction::TYPE_EXPENSE, 'description' => 'Paid rent']);

        // Pay utility
        Transaction::create(['bucket_id' => $utilities->id, 'amount' => -35000, 'type' => Transaction::TYPE_EXPENSE, 'description' => 'Electricity']);

        // Transfer leftover utilities to savings (atomic)
        $ref = Str::uuid()->toString();
        DB::transaction(function () use ($utilities, $savings, $ref) {
            Transaction::create(['bucket_id' => $utilities->id, 'amount' => -15000, 'type' => Transaction::TYPE_TRANSFER, 'reference_id' => $ref]);
            Transaction::create(['bucket_id' => $savings->id, 'amount' => 15000, 'type' => Transaction::TYPE_TRANSFER, 'reference_id' => $ref]);
        });

        $this->assertEquals(0, $rent->balance);
        $this->assertEquals(0, $utilities->balance);
        $this->assertEquals(65000, $savings->balance);

        // Total in system should equal original deposit
        $this->assertEquals(250000, Transaction::whereNotNull('deposit_id')->sum('amount'));
        // Transfers and expenses net out within the system
        $this->assertEquals(65000, Transaction::sum('amount'));
    }

    public function test_transaction_type_constants_exist(): void
    {
        $this->assertEquals('allocation', Transaction::TYPE_ALLOCATION);
        $this->assertEquals('expense', Transaction::TYPE_EXPENSE);
        $this->assertEquals('sweep', Transaction::TYPE_SWEEP);
        $this->assertEquals('transfer', Transaction::TYPE_TRANSFER);
    }
}
