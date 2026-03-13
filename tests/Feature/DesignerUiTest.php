<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignerUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_does_not_contain_new_bucket_button(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        // The standalone "New Bucket" circle-plus link at the bottom of the sidebar should be removed
        $response->assertDontSee('title="New Bucket"', escape: false);
    }

    public function test_sidebar_shows_active_state_for_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        // Active sidebar item should have a gold indicator
        $response->assertSee('bg-gold rounded-full', escape: false);
    }

    public function test_sidebar_shows_active_state_for_buckets(): void
    {
        Bucket::factory()->fixed()->create(['monthly_target' => 100000, 'priority_order' => 1]);

        $response = $this->get(route('buckets.index'));

        $response->assertOk();
        $response->assertSee('bg-gold rounded-full', escape: false);
    }

    public function test_flash_message_has_dismiss_button(): void
    {
        Bucket::factory()->fixed()->create(['monthly_target' => 100000, 'priority_order' => 1]);

        $response = $this->post(route('buckets.store'), [
            'name' => 'Test Bucket',
            'type' => 'fixed',
            'monthly_target' => '500.00',
            'priority_order' => 2,
        ]);

        $response = $this->get(route('buckets.index'));

        // Flash message should have Alpine auto-dismiss and a close button
        $response->assertSee('x-data="{ show: true }"', escape: false);
    }

    public function test_empty_bucket_state_has_cta_button(): void
    {
        $response = $this->get(route('buckets.index'));

        $response->assertOk();
        $response->assertSee('Create Your First Bucket');
    }

    public function test_bucket_show_balance_uses_serif_typography(): void
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

        $response = $this->get(route('buckets.show', $bucket));

        $response->assertOk();
        // Balance should use serif font for hero treatment
        $response->assertSee('font-serif text-5xl', escape: false);
    }
}
