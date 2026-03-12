<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Bucket;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BucketTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_a_fixed_bucket(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Mortgage',
            'monthly_target' => 150000,
            'priority_order' => 1,
        ]);

        $this->assertDatabaseHas('buckets', [
            'id' => $bucket->id,
            'name' => 'Mortgage',
            'type' => 'fixed',
            'monthly_target' => 150000,
            'priority_order' => 1,
        ]);
    }

    public function test_it_can_create_an_excess_bucket(): void
    {
        $bucket = Bucket::factory()->excess()->create([
            'name' => 'Fun Money',
            'excess_percentage' => 30,
        ]);

        $this->assertDatabaseHas('buckets', [
            'id' => $bucket->id,
            'name' => 'Fun Money',
            'type' => 'excess',
            'excess_percentage' => 30,
        ]);
    }

    public function test_it_soft_deletes(): void
    {
        $bucket = Bucket::factory()->fixed()->create();

        $bucket->delete();

        $this->assertSoftDeleted('buckets', ['id' => $bucket->id]);
        $this->assertDatabaseHas('buckets', ['id' => $bucket->id]);
    }

    public function test_balance_is_calculated_from_transactions(): void
    {
        $bucket = Bucket::factory()->fixed()->create();

        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'amount' => 50000,
            'type' => 'allocation',
        ]);

        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'amount' => -15000,
            'type' => 'expense',
        ]);

        $this->assertEquals(35000, $bucket->balance);
    }

    public function test_balance_is_zero_with_no_transactions(): void
    {
        $bucket = Bucket::factory()->fixed()->create();

        $this->assertEquals(0, $bucket->balance);
    }

    public function test_bucket_has_many_transactions(): void
    {
        $bucket = Bucket::factory()->fixed()->create();

        Transaction::factory()->count(3)->create([
            'bucket_id' => $bucket->id,
        ]);

        $this->assertCount(3, $bucket->transactions);
    }

    public function test_balance_uses_eager_loaded_transactions_without_extra_query(): void
    {
        $bucket = Bucket::factory()->fixed()->create();

        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'amount' => 20000,
            'type' => 'allocation',
        ]);

        $loaded = Bucket::with('transactions')->find($bucket->id);

        $queryCount = 0;
        \DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $this->assertEquals(20000, $loaded->balance);
        $this->assertEquals(0, $queryCount);
    }

    public function test_type_constants_match_expected_values(): void
    {
        $this->assertEquals('fixed', Bucket::TYPE_FIXED);
        $this->assertEquals('excess', Bucket::TYPE_EXCESS);
    }

    public function test_bucket_cannot_be_hard_deleted_with_transactions(): void
    {
        $bucket = Bucket::factory()->fixed()->create();

        Transaction::factory()->create(['bucket_id' => $bucket->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $bucket->forceDelete();
    }
}
