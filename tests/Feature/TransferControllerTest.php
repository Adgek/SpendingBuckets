<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_transfer_creates_paired_transactions(): void
    {
        $source = Bucket::factory()->fixed()->create([
            'name' => 'Groceries',
            'monthly_target' => 50000,
            'priority_order' => 1,
        ]);
        $destination = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 2,
        ]);

        // Fund the source bucket
        Transaction::factory()->create([
            'bucket_id' => $source->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $response = $this->postJson('/api/transfers', [
            'source_bucket_id' => $source->id,
            'destination_bucket_id' => $destination->id,
            'amount' => 20000,
            'description' => 'Cover rent shortfall',
        ]);

        $response->assertStatus(201);

        // Should have two transfer transactions with the same reference_id
        $transfers = Transaction::where('type', Transaction::TYPE_TRANSFER)->get();
        $this->assertCount(2, $transfers);

        $negative = $transfers->where('amount', -20000)->first();
        $positive = $transfers->where('amount', 20000)->first();

        $this->assertNotNull($negative);
        $this->assertNotNull($positive);
        $this->assertEquals($source->id, $negative->bucket_id);
        $this->assertEquals($destination->id, $positive->bucket_id);
        $this->assertNotNull($negative->reference_id);
        $this->assertEquals($negative->reference_id, $positive->reference_id);
    }

    public function test_store_transfer_validates_required_fields(): void
    {
        $response = $this->postJson('/api/transfers', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'source_bucket_id',
            'destination_bucket_id',
            'amount',
        ]);
    }

    public function test_store_transfer_validates_buckets_exist(): void
    {
        $response = $this->postJson('/api/transfers', [
            'source_bucket_id' => 9999,
            'destination_bucket_id' => 8888,
            'amount' => 1000,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['source_bucket_id', 'destination_bucket_id']);
    }

    public function test_store_transfer_validates_source_and_destination_differ(): void
    {
        $bucket = Bucket::factory()->create();

        $response = $this->postJson('/api/transfers', [
            'source_bucket_id' => $bucket->id,
            'destination_bucket_id' => $bucket->id,
            'amount' => 1000,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['destination_bucket_id']);
    }

    public function test_store_transfer_validates_amount_is_positive(): void
    {
        $source = Bucket::factory()->create();
        $destination = Bucket::factory()->create();

        $response = $this->postJson('/api/transfers', [
            'source_bucket_id' => $source->id,
            'destination_bucket_id' => $destination->id,
            'amount' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }
}
