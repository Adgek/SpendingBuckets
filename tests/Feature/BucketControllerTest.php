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

    public function test_index_displays_all_buckets_with_balance(): void
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

        $response = $this->get('/buckets');

        $response->assertOk();
        $response->assertViewIs('buckets.index');
        $response->assertViewHas('buckets');
        $response->assertSee('Rent');
    }

    public function test_create_displays_form(): void
    {
        $response = $this->get('/buckets/create');

        $response->assertOk();
        $response->assertViewIs('buckets.create');
    }

    public function test_store_creates_a_new_bucket_and_redirects(): void
    {
        $response = $this->post('/buckets', [
            'name' => 'Groceries',
            'type' => 'fixed',
            'monthly_target' => 60000,
            'priority_order' => 1,
        ]);

        $response->assertRedirect('/buckets');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('buckets', [
            'name' => 'Groceries',
            'type' => 'fixed',
            'monthly_target' => 60000,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->post('/buckets', []);

        $response->assertSessionHasErrors(['name', 'type']);
    }

    public function test_store_validates_type_enum(): void
    {
        $response = $this->post('/buckets', [
            'name' => 'Bad Bucket',
            'type' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['type']);
    }

    public function test_store_validates_fixed_bucket_requires_monthly_target(): void
    {
        $response = $this->post('/buckets', [
            'name' => 'Fixed Bucket',
            'type' => 'fixed',
            'priority_order' => 1,
        ]);

        $response->assertSessionHasErrors(['monthly_target']);
    }

    public function test_edit_displays_form_with_bucket(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 100000,
            'priority_order' => 1,
        ]);

        $response = $this->get("/buckets/{$bucket->id}/edit");

        $response->assertOk();
        $response->assertViewIs('buckets.edit');
        $response->assertViewHas('bucket');
    }

    public function test_update_modifies_bucket_and_redirects(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Old Name',
            'monthly_target' => 50000,
            'priority_order' => 1,
        ]);

        $response = $this->put("/buckets/{$bucket->id}", [
            'name' => 'New Name',
            'type' => 'fixed',
            'monthly_target' => 75000,
            'priority_order' => 1,
        ]);

        $response->assertRedirect('/buckets');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('buckets', [
            'id' => $bucket->id,
            'name' => 'New Name',
            'monthly_target' => 75000,
        ]);
    }

    public function test_destroy_soft_deletes_bucket_and_redirects(): void
    {
        $bucket = Bucket::factory()->create(['name' => 'To Delete']);

        $response = $this->delete("/buckets/{$bucket->id}");

        $response->assertRedirect('/buckets');
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('buckets', ['id' => $bucket->id]);
    }

    public function test_show_displays_single_bucket_with_balance(): void
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

        $response = $this->get("/buckets/{$bucket->id}");

        $response->assertOk();
        $response->assertViewIs('buckets.show');
        $response->assertViewHas('bucket');
        $response->assertSee('Rent');
    }
}
