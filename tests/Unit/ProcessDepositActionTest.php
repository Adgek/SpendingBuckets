<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\ProcessDepositAction;
use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessDepositActionTest extends TestCase
{
    use RefreshDatabase;

    private ProcessDepositAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ProcessDepositAction();
    }

    // -------------------------------------------------------
    // Sequential Fill (Fixed Buckets)
    // -------------------------------------------------------

    public function test_fills_single_fixed_bucket_fully(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 150000,
            'priority_order' => 1,
        ]);

        $deposit = Deposit::factory()->create([
            'amount' => 200000,
            'deposit_date' => '2025-07-15',
        ]);

        $this->action->execute($deposit);

        $this->assertEquals(150000, $bucket->balance);
        $this->assertDatabaseHas('transactions', [
            'bucket_id' => $bucket->id,
            'deposit_id' => $deposit->id,
            'amount' => 150000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);
    }

    public function test_fills_multiple_fixed_buckets_in_priority_order(): void
    {
        $rent = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 150000,
            'priority_order' => 1,
        ]);
        $utilities = Bucket::factory()->fixed()->create([
            'name' => 'Utilities',
            'monthly_target' => 50000,
            'priority_order' => 2,
        ]);
        $groceries = Bucket::factory()->fixed()->create([
            'name' => 'Groceries',
            'monthly_target' => 60000,
            'priority_order' => 3,
        ]);

        $deposit = Deposit::factory()->create([
            'amount' => 300000,
            'deposit_date' => '2025-07-15',
        ]);

        $this->action->execute($deposit);

        $this->assertEquals(150000, $rent->balance);
        $this->assertEquals(50000, $utilities->balance);
        $this->assertEquals(60000, $groceries->balance);
    }

    // -------------------------------------------------------
    // Shortfall Scenario
    // -------------------------------------------------------

    public function test_shortfall_partially_fills_bucket_and_halts(): void
    {
        $rent = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 150000,
            'priority_order' => 1,
        ]);
        $utilities = Bucket::factory()->fixed()->create([
            'name' => 'Utilities',
            'monthly_target' => 50000,
            'priority_order' => 2,
        ]);

        $deposit = Deposit::factory()->create([
            'amount' => 175000,
            'deposit_date' => '2025-07-15',
        ]);

        $this->action->execute($deposit);

        $this->assertEquals(150000, $rent->balance);
        $this->assertEquals(25000, $utilities->balance);
    }

    public function test_shortfall_fills_nothing_after_funds_exhausted(): void
    {
        $rent = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 150000,
            'priority_order' => 1,
        ]);
        $utilities = Bucket::factory()->fixed()->create([
            'name' => 'Utilities',
            'monthly_target' => 50000,
            'priority_order' => 2,
        ]);
        $groceries = Bucket::factory()->fixed()->create([
            'name' => 'Groceries',
            'monthly_target' => 60000,
            'priority_order' => 3,
        ]);

        $deposit = Deposit::factory()->create([
            'amount' => 150000,
            'deposit_date' => '2025-07-15',
        ]);

        $this->action->execute($deposit);

        $this->assertEquals(150000, $rent->balance);
        $this->assertEquals(0, $utilities->balance);
        $this->assertEquals(0, $groceries->balance);

        // Only one allocation transaction created
        $this->assertEquals(1, Transaction::where('deposit_id', $deposit->id)->count());
    }

    // -------------------------------------------------------
    // Multiple Deposits in Same Month (5-Paycheck Scenario)
    // -------------------------------------------------------

    public function test_second_deposit_in_month_respects_already_funded_amounts(): void
    {
        $rent = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 150000,
            'priority_order' => 1,
        ]);
        $utilities = Bucket::factory()->fixed()->create([
            'name' => 'Utilities',
            'monthly_target' => 50000,
            'priority_order' => 2,
        ]);

        // First deposit covers rent fully, utilities partially
        $deposit1 = Deposit::factory()->create([
            'amount' => 175000,
            'deposit_date' => '2025-07-01',
        ]);
        $this->action->execute($deposit1);

        $this->assertEquals(150000, $rent->balance);
        $this->assertEquals(25000, $utilities->balance);

        // Second deposit in same month: rent already filled, utilities needs 25000 more
        $deposit2 = Deposit::factory()->create([
            'amount' => 100000,
            'deposit_date' => '2025-07-15',
        ]);
        $this->action->execute($deposit2);

        $this->assertEquals(150000, $rent->balance); // No change
        $this->assertEquals(50000, $utilities->balance); // Now fully funded
    }

    // -------------------------------------------------------
    // Excess Distribution
    // -------------------------------------------------------

    public function test_excess_funds_distributed_to_excess_buckets_by_percentage(): void
    {
        $rent = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        $savings = Bucket::factory()->excess()->create([
            'name' => 'Savings',
            'excess_percentage' => 60,
        ]);
        $vacation = Bucket::factory()->excess()->create([
            'name' => 'Vacation',
            'excess_percentage' => 40,
        ]);

        $deposit = Deposit::factory()->create([
            'amount' => 200000,
            'deposit_date' => '2025-07-15',
        ]);

        $this->action->execute($deposit);

        $this->assertEquals(100000, $rent->balance);
        $this->assertEquals(60000, $savings->balance);
        $this->assertEquals(40000, $vacation->balance);
    }

    public function test_excess_with_no_excess_buckets_goes_to_savings(): void
    {
        $rent = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        // Create a savings bucket (excess, primary savings)
        $savings = Bucket::factory()->excess()->create([
            'name' => 'Savings',
            'excess_percentage' => 100,
        ]);

        $deposit = Deposit::factory()->create([
            'amount' => 150000,
            'deposit_date' => '2025-07-15',
        ]);

        $this->action->execute($deposit);

        $this->assertEquals(100000, $rent->balance);
        $this->assertEquals(50000, $savings->balance);
    }

    // -------------------------------------------------------
    // Cap Check on Excess Buckets
    // -------------------------------------------------------

    public function test_excess_bucket_cap_limits_allocation(): void
    {
        $rent = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        $vacation = Bucket::factory()->excess()->create([
            'name' => 'Vacation',
            'excess_percentage' => 50,
            'cap' => 20000,
        ]);
        $savings = Bucket::factory()->excess()->create([
            'name' => 'Savings',
            'excess_percentage' => 50,
            'cap' => null, // No cap — acts as the catch-all
        ]);

        $deposit = Deposit::factory()->create([
            'amount' => 200000,
            'deposit_date' => '2025-07-15',
        ]);

        $this->action->execute($deposit);

        $this->assertEquals(100000, $rent->balance);
        // Vacation: 50% of 100000 = 50000, but capped at 20000
        $this->assertEquals(20000, $vacation->balance);
        // Savings: 50% of 100000 = 50000 + 30000 overflow from vacation = 80000
        $this->assertEquals(80000, $savings->balance);
    }

    public function test_excess_bucket_cap_respects_existing_balance(): void
    {
        $rent = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        $vacation = Bucket::factory()->excess()->create([
            'name' => 'Vacation',
            'excess_percentage' => 50,
            'cap' => 30000,
        ]);
        $savings = Bucket::factory()->excess()->create([
            'name' => 'Savings',
            'excess_percentage' => 50,
            'cap' => null,
        ]);

        // Pre-fund vacation with 20000 from a previous deposit
        Transaction::create([
            'bucket_id' => $vacation->id,
            'amount' => 20000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $deposit = Deposit::factory()->create([
            'amount' => 200000,
            'deposit_date' => '2025-07-15',
        ]);

        $this->action->execute($deposit);

        $this->assertEquals(100000, $rent->balance);
        // Vacation: 50% of 100000 = 50000, but only 10000 room left (cap 30000 - existing 20000)
        $this->assertEquals(30000, $vacation->balance);
        // Savings: 50000 (its share) + 40000 (overflow from vacation) = 90000
        $this->assertEquals(90000, $savings->balance);
    }

    // -------------------------------------------------------
    // Leftover Cents / Catch-All Savings
    // -------------------------------------------------------

    public function test_unallocatable_remainder_goes_to_primary_savings(): void
    {
        $rent = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        // Both excess buckets have caps
        $vacation = Bucket::factory()->excess()->create([
            'name' => 'Vacation',
            'excess_percentage' => 50,
            'cap' => 10000,
        ]);
        $savings = Bucket::factory()->excess()->create([
            'name' => 'Savings',
            'excess_percentage' => 50,
            'cap' => null,
        ]);

        $deposit = Deposit::factory()->create([
            'amount' => 200000,
            'deposit_date' => '2025-07-15',
        ]);

        $this->action->execute($deposit);

        $this->assertEquals(100000, $rent->balance);
        $this->assertEquals(10000, $vacation->balance);
        // Savings gets its 50% share (50000) + overflow from vacation (40000) = 90000
        $this->assertEquals(90000, $savings->balance);
    }

    // -------------------------------------------------------
    // Edge Cases
    // -------------------------------------------------------

    public function test_zero_deposit_creates_no_transactions(): void
    {
        Bucket::factory()->fixed()->create([
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        $deposit = Deposit::factory()->create([
            'amount' => 0,
            'deposit_date' => '2025-07-15',
        ]);

        $this->action->execute($deposit);

        $this->assertEquals(0, Transaction::count());
    }

    public function test_deposit_with_no_fixed_buckets_all_goes_to_excess(): void
    {
        $savings = Bucket::factory()->excess()->create([
            'name' => 'Savings',
            'excess_percentage' => 70,
        ]);
        $fun = Bucket::factory()->excess()->create([
            'name' => 'Fun Money',
            'excess_percentage' => 30,
        ]);

        $deposit = Deposit::factory()->create([
            'amount' => 100000,
            'deposit_date' => '2025-07-15',
        ]);

        $this->action->execute($deposit);

        $this->assertEquals(70000, $savings->balance);
        $this->assertEquals(30000, $fun->balance);
    }

    public function test_all_transactions_linked_to_deposit(): void
    {
        Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);
        Bucket::factory()->excess()->create([
            'name' => 'Savings',
            'excess_percentage' => 100,
        ]);

        $deposit = Deposit::factory()->create([
            'amount' => 150000,
            'deposit_date' => '2025-07-15',
        ]);

        $this->action->execute($deposit);

        $allTransactions = Transaction::where('deposit_id', $deposit->id)->get();
        $this->assertCount(2, $allTransactions);
        $this->assertEquals(150000, $allTransactions->sum('amount'));
    }

    public function test_rounding_issue_odd_percentages(): void
    {
        $rent = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        // 33 + 33 + 34 = 100
        $bucket1 = Bucket::factory()->excess()->create([
            'name' => 'A',
            'excess_percentage' => 33,
        ]);
        $bucket2 = Bucket::factory()->excess()->create([
            'name' => 'B',
            'excess_percentage' => 33,
        ]);
        $savings = Bucket::factory()->excess()->create([
            'name' => 'Savings',
            'excess_percentage' => 34,
        ]);

        $deposit = Deposit::factory()->create([
            'amount' => 200001, // 100001 remaining → test rounding
            'deposit_date' => '2025-07-15',
        ]);

        $this->action->execute($deposit);

        $this->assertEquals(100000, $rent->balance);

        // Total allocated to excess must equal exactly 100001
        $excessTotal = $bucket1->balance + $bucket2->balance + $savings->balance;
        $this->assertEquals(100001, $excessTotal);
    }
}
