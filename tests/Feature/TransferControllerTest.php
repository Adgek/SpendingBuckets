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

    public function test_create_displays_transfer_form_with_buckets(): void
    {
        Bucket::factory()->fixed()->create(['name' => 'Groceries', 'priority_order' => 1]);
        Bucket::factory()->fixed()->create(['name' => 'Rent', 'priority_order' => 2]);

        $response = $this->get(route('transfers.create'));

        $response->assertOk();
        $response->assertViewIs('transfers.create');
        $response->assertViewHas('buckets');
    }

    public function test_store_transfer_converts_dollars_to_cents_and_redirects(): void
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

        Transaction::factory()->create([
            'bucket_id' => $source->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $response = $this->post(route('transfers.store'), [
            'source_bucket_id' => $source->id,
            'destination_bucket_id' => $destination->id,
            'amount' => '200.00',
            'description' => 'Cover rent shortfall',
        ]);

        $response->assertRedirect(route('buckets.index'));
        $response->assertSessionHas('success');

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
        $response = $this->post(route('transfers.store'), []);

        $response->assertSessionHasErrors([
            'source_bucket_id',
            'destination_bucket_id',
            'amount',
        ]);
    }

    public function test_store_transfer_validates_buckets_exist(): void
    {
        $response = $this->post(route('transfers.store'), [
            'source_bucket_id' => 9999,
            'destination_bucket_id' => 8888,
            'amount' => '10.00',
        ]);

        $response->assertSessionHasErrors(['source_bucket_id', 'destination_bucket_id']);
    }

    public function test_store_transfer_validates_source_and_destination_differ(): void
    {
        $bucket = Bucket::factory()->create();

        $response = $this->post(route('transfers.store'), [
            'source_bucket_id' => $bucket->id,
            'destination_bucket_id' => $bucket->id,
            'amount' => '10.00',
        ]);

        $response->assertSessionHasErrors(['destination_bucket_id']);
    }

    public function test_store_transfer_validates_amount_is_positive(): void
    {
        $source = Bucket::factory()->create();
        $destination = Bucket::factory()->create();

        $response = $this->post(route('transfers.store'), [
            'source_bucket_id' => $source->id,
            'destination_bucket_id' => $destination->id,
            'amount' => '0',
        ]);

        $response->assertSessionHasErrors(['amount']);
    }
}
