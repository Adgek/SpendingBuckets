<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BucketControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_buckets_with_balance(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'amount' => 75000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $response = $this->getJson('/api/buckets');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'name' => 'Rent',
            'balance' => 75000,
        ]);
    }

    public function test_store_creates_a_new_bucket(): void
    {
        $response = $this->postJson('/api/buckets', [
            'name' => 'Groceries',
            'type' => 'fixed',
            'monthly_target' => 60000,
            'priority_order' => 1,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('buckets', [
            'name' => 'Groceries',
            'type' => 'fixed',
            'monthly_target' => 60000,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->postJson('/api/buckets', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'type']);
    }

    public function test_store_validates_type_enum(): void
    {
        $response = $this->postJson('/api/buckets', [
            'name' => 'Bad Bucket',
            'type' => 'invalid',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    public function test_store_validates_fixed_bucket_requires_monthly_target(): void
    {
        $response = $this->postJson('/api/buckets', [
            'name' => 'Fixed Bucket',
            'type' => 'fixed',
            'priority_order' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['monthly_target']);
    }

    public function test_update_modifies_bucket(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Old Name',
            'monthly_target' => 50000,
            'priority_order' => 1,
        ]);

        $response = $this->putJson("/api/buckets/{$bucket->id}", [
            'name' => 'New Name',
            'type' => 'fixed',
            'monthly_target' => 75000,
            'priority_order' => 1,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('buckets', [
            'id' => $bucket->id,
            'name' => 'New Name',
            'monthly_target' => 75000,
        ]);
    }

    public function test_destroy_soft_deletes_bucket(): void
    {
        $bucket = Bucket::factory()->create(['name' => 'To Delete']);

        $response = $this->deleteJson("/api/buckets/{$bucket->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('buckets', ['id' => $bucket->id]);
    }

    public function test_show_returns_single_bucket_with_balance(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $response = $this->getJson("/api/buckets/{$bucket->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'Rent',
            'balance' => 50000,
        ]);
    }
}
