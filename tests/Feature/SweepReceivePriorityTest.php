<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SweepReceivePriorityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sweep_distributes_to_sweep_receive_buckets_by_priority_then_primary_savings(): void
    {
        $savings = Bucket::factory()->create([
            'name' => 'Savings',
            'type' => 'excess',
            'is_primary_savings' => true,
            'sweeps_excess' => false,
        ]);

        $emergencyFund = Bucket::factory()->create([
            'name' => 'Emergency Fund',
            'type' => 'excess',
            'cap' => 50000, // $500 cap
            'receives_sweeps' => true,
            'priority_order' => 1,
            'sweeps_excess' => false,
        ]);

        $vacation = Bucket::factory()->create([
            'name' => 'Vacation',
            'type' => 'excess',
            'cap' => 30000, // $300 cap
            'receives_sweeps' => true,
            'priority_order' => 2,
            'sweeps_excess' => false,
        ]);

        // Sweepable bucket with $1,000 balance
        $groceries = Bucket::factory()->create([
            'name' => 'Groceries',
            'type' => 'fixed',
            'monthly_target' => 60000,
            'sweeps_excess' => true,
        ]);

        Transaction::factory()->create([
            'bucket_id' => $groceries->id,
            'amount' => 100000, // $1,000
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $this->post(route('sweep.store'));

        // Emergency fund should get $500 (filled to cap)
        $this->assertEquals(50000, $emergencyFund->fresh()->balance);
        // Vacation should get $300 (filled to cap)
        $this->assertEquals(30000, $vacation->fresh()->balance);
        // Remaining $200 goes to primary savings
        $this->assertEquals(20000, $savings->fresh()->balance);
        // Groceries swept to 0
        $this->assertEquals(0, $groceries->fresh()->balance);
    }

    public function test_sweep_respects_existing_balance_against_cap(): void
    {
        $savings = Bucket::factory()->create([
            'name' => 'Savings',
            'type' => 'excess',
            'is_primary_savings' => true,
            'sweeps_excess' => false,
        ]);

        $emergencyFund = Bucket::factory()->create([
            'name' => 'Emergency Fund',
            'type' => 'excess',
            'cap' => 50000, // $500 cap
            'receives_sweeps' => true,
            'priority_order' => 1,
            'sweeps_excess' => false,
        ]);

        // Emergency fund already has $400
        Transaction::factory()->create([
            'bucket_id' => $emergencyFund->id,
            'amount' => 40000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $groceries = Bucket::factory()->create([
            'name' => 'Groceries',
            'type' => 'fixed',
            'monthly_target' => 60000,
            'sweeps_excess' => true,
        ]);

        Transaction::factory()->create([
            'bucket_id' => $groceries->id,
            'amount' => 30000, // $300
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $this->post(route('sweep.store'));

        // Emergency fund should only get $100 more (to reach $500 cap)
        $this->assertEquals(50000, $emergencyFund->fresh()->balance);
        // Remaining $200 goes to primary savings
        $this->assertEquals(20000, $savings->fresh()->balance);
    }

    public function test_sweep_skips_receive_bucket_already_at_cap(): void
    {
        $savings = Bucket::factory()->create([
            'name' => 'Savings',
            'type' => 'excess',
            'is_primary_savings' => true,
            'sweeps_excess' => false,
        ]);

        $emergencyFund = Bucket::factory()->create([
            'name' => 'Emergency Fund',
            'type' => 'excess',
            'cap' => 50000,
            'receives_sweeps' => true,
            'priority_order' => 1,
            'sweeps_excess' => false,
        ]);

        // Already at cap
        Transaction::factory()->create([
            'bucket_id' => $emergencyFund->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $groceries = Bucket::factory()->create([
            'name' => 'Groceries',
            'type' => 'fixed',
            'monthly_target' => 60000,
            'sweeps_excess' => true,
        ]);

        Transaction::factory()->create([
            'bucket_id' => $groceries->id,
            'amount' => 20000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $this->post(route('sweep.store'));

        // Emergency fund unchanged
        $this->assertEquals(50000, $emergencyFund->fresh()->balance);
        // All $200 goes to primary savings
        $this->assertEquals(20000, $savings->fresh()->balance);
    }

    public function test_sweep_receive_bucket_without_cap_takes_all_remaining(): void
    {
        $savings = Bucket::factory()->create([
            'name' => 'Savings',
            'type' => 'excess',
            'is_primary_savings' => true,
            'sweeps_excess' => false,
        ]);

        $emergencyFund = Bucket::factory()->create([
            'name' => 'Emergency Fund',
            'type' => 'excess',
            'cap' => null, // no cap
            'receives_sweeps' => true,
            'priority_order' => 1,
            'sweeps_excess' => false,
        ]);

        $groceries = Bucket::factory()->create([
            'name' => 'Groceries',
            'type' => 'fixed',
            'monthly_target' => 60000,
            'sweeps_excess' => true,
        ]);

        Transaction::factory()->create([
            'bucket_id' => $groceries->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $this->post(route('sweep.store'));

        // Emergency fund gets all $500 (no cap)
        $this->assertEquals(50000, $emergencyFund->fresh()->balance);
        // Nothing left for savings
        $this->assertEquals(0, $savings->fresh()->balance);
    }

    public function test_enforce_single_primary_savings_on_create(): void
    {
        Bucket::factory()->create([
            'name' => 'Existing Savings',
            'type' => 'excess',
            'is_primary_savings' => true,
        ]);

        $response = $this->post(route('buckets.store'), [
            'name' => 'Second Savings',
            'type' => 'excess',
            'is_primary_savings' => '1',
        ]);

        $response->assertSessionHasErrors('is_primary_savings');
    }

    public function test_enforce_single_primary_savings_on_update(): void
    {
        Bucket::factory()->create([
            'name' => 'Existing Savings',
            'type' => 'excess',
            'is_primary_savings' => true,
        ]);

        $otherBucket = Bucket::factory()->create([
            'name' => 'Other',
            'type' => 'excess',
            'is_primary_savings' => false,
        ]);

        $response = $this->put(route('buckets.update', $otherBucket), [
            'name' => 'Other',
            'type' => 'excess',
            'is_primary_savings' => '1',
        ]);

        $response->assertSessionHasErrors('is_primary_savings');
    }

    public function test_existing_primary_savings_can_update_itself(): void
    {
        $savings = Bucket::factory()->create([
            'name' => 'Savings',
            'type' => 'excess',
            'is_primary_savings' => true,
        ]);

        $response = $this->put(route('buckets.update', $savings), [
            'name' => 'My Savings',
            'type' => 'excess',
            'is_primary_savings' => '1',
        ]);

        $response->assertRedirect(route('buckets.index'));
        $response->assertSessionHas('success');
    }
}
