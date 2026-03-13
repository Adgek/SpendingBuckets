<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BucketReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reorder_updates_priority_order_for_fixed_buckets(): void
    {
        $bucketA = Bucket::factory()->create(['type' => 'fixed', 'monthly_target' => 10000, 'priority_order' => 1, 'name' => 'A']);
        $bucketB = Bucket::factory()->create(['type' => 'fixed', 'monthly_target' => 20000, 'priority_order' => 2, 'name' => 'B']);
        $bucketC = Bucket::factory()->create(['type' => 'fixed', 'monthly_target' => 30000, 'priority_order' => 3, 'name' => 'C']);

        $response = $this->putJson(route('buckets.reorder'), [
            'order' => [$bucketC->id, $bucketA->id, $bucketB->id],
        ]);

        $response->assertOk();
        $response->assertJson(['message' => 'Priority order updated.']);

        $this->assertDatabaseHas('buckets', ['id' => $bucketC->id, 'priority_order' => 1]);
        $this->assertDatabaseHas('buckets', ['id' => $bucketA->id, 'priority_order' => 2]);
        $this->assertDatabaseHas('buckets', ['id' => $bucketB->id, 'priority_order' => 3]);
    }

    public function test_reorder_validates_order_field_is_required(): void
    {
        $response = $this->putJson(route('buckets.reorder'), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('order');
    }

    public function test_reorder_validates_order_contains_valid_bucket_ids(): void
    {
        $response = $this->putJson(route('buckets.reorder'), [
            'order' => [999, 998],
        ]);

        $response->assertUnprocessable();
    }

    public function test_reorder_validates_order_is_array(): void
    {
        $response = $this->putJson(route('buckets.reorder'), [
            'order' => 'not-an-array',
        ]);

        $response->assertUnprocessable();
    }
}
