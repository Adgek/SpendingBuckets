<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SweepControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sweep_page_loads(): void
    {
        $response = $this->get(route('sweep.create'));

        $response->assertOk();
    }

    public function test_sweep_transfers_eligible_bucket_balances_to_primary_savings(): void
    {
        $savings = Bucket::factory()->create([
            'name' => 'Savings',
            'type' => 'excess',
            'is_primary_savings' => true,
            'sweeps_excess' => false,
        ]);

        $groceries = Bucket::factory()->create([
            'name' => 'Groceries',
            'type' => 'fixed',
            'monthly_target' => 50000,
            'sweeps_excess' => true,
        ]);

        $emergency = Bucket::factory()->create([
            'name' => 'Emergency',
            'type' => 'fixed',
            'monthly_target' => 100000,
            'sweeps_excess' => false,
        ]);

        // Give groceries a balance of $200
        Transaction::factory()->create([
            'bucket_id' => $groceries->id,
            'amount' => 20000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        // Give emergency a balance of $500
        Transaction::factory()->create([
            'bucket_id' => $emergency->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $response = $this->post(route('sweep.store'));

        $response->assertRedirect(route('buckets.index'));
        $response->assertSessionHas('success');

        // Groceries should be swept to 0
        $this->assertEquals(0, $groceries->fresh()->balance);

        // Emergency should remain untouched (sweeps_excess = false)
        $this->assertEquals(50000, $emergency->fresh()->balance);

        // Savings should have received the $200 from groceries
        $this->assertEquals(20000, $savings->fresh()->balance);

        // Verify sweep transactions exist
        $this->assertDatabaseHas('transactions', [
            'bucket_id' => $groceries->id,
            'amount' => -20000,
            'type' => Transaction::TYPE_SWEEP,
        ]);

        $this->assertDatabaseHas('transactions', [
            'bucket_id' => $savings->id,
            'amount' => 20000,
            'type' => Transaction::TYPE_SWEEP,
        ]);

        // All sweep transactions share a single reference_id
        $sweepTxns = Transaction::where('type', Transaction::TYPE_SWEEP)->get();
        $this->assertEquals(1, $sweepTxns->pluck('reference_id')->unique()->count());
    }

    public function test_sweep_skips_buckets_with_zero_balance(): void
    {
        $savings = Bucket::factory()->create([
            'name' => 'Savings',
            'type' => 'excess',
            'is_primary_savings' => true,
        ]);

        $water = Bucket::factory()->create([
            'name' => 'Water',
            'type' => 'fixed',
            'monthly_target' => 10000,
            'sweeps_excess' => true,
        ]);

        $response = $this->post(route('sweep.store'));

        $response->assertRedirect(route('buckets.index'));

        // No sweep transactions should be created
        $this->assertEquals(0, Transaction::where('type', Transaction::TYPE_SWEEP)->count());
    }

    public function test_sweep_fails_without_primary_savings_bucket(): void
    {
        Bucket::factory()->create([
            'name' => 'Groceries',
            'type' => 'fixed',
            'monthly_target' => 50000,
            'sweeps_excess' => true,
        ]);

        Transaction::factory()->create([
            'bucket_id' => Bucket::first()->id,
            'amount' => 10000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $response = $this->post(route('sweep.store'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_sweep_accepts_month_parameter(): void
    {
        $savings = Bucket::factory()->create([
            'name' => 'Savings',
            'type' => 'excess',
            'is_primary_savings' => true,
        ]);

        $groceries = Bucket::factory()->create([
            'name' => 'Groceries',
            'type' => 'fixed',
            'monthly_target' => 50000,
            'sweeps_excess' => true,
        ]);

        Transaction::factory()->create([
            'bucket_id' => $groceries->id,
            'amount' => 20000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $response = $this->post(route('sweep.store'), [
            'month' => now()->format('Y-m'),
        ]);

        $response->assertRedirect(route('buckets.index'));
        $this->assertEquals(0, $groceries->fresh()->balance);
    }

    public function test_sweep_prevents_double_sweep_in_same_month(): void
    {
        $savings = Bucket::factory()->create([
            'name' => 'Savings',
            'type' => 'excess',
            'is_primary_savings' => true,
        ]);

        $groceries = Bucket::factory()->create([
            'name' => 'Groceries',
            'type' => 'fixed',
            'monthly_target' => 50000,
            'sweeps_excess' => true,
        ]);

        Transaction::factory()->create([
            'bucket_id' => $groceries->id,
            'amount' => 20000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $month = now()->format('Y-m');

        // First sweep succeeds
        $this->post(route('sweep.store'), ['month' => $month]);
        $this->assertEquals(0, $groceries->fresh()->balance);

        // Fund groceries again
        Transaction::factory()->create([
            'bucket_id' => $groceries->id,
            'amount' => 15000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        // Second sweep for same month is blocked
        $response = $this->post(route('sweep.store'), ['month' => $month]);
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Groceries balance should remain (not swept again)
        $this->assertEquals(15000, $groceries->fresh()->balance);
    }
}
