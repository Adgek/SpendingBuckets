<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BucketPriorityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $names  in the priority order they should hold
     */
    private function assertStackIs(array $names): void
    {
        $actual = Bucket::whereNotNull('priority_order')
            ->orderBy('priority_order')
            ->pluck('name')
            ->all();

        $this->assertSame($names, $actual);

        $orders = Bucket::whereNotNull('priority_order')
            ->orderBy('priority_order')
            ->pluck('priority_order')
            ->all();

        $this->assertSame(range(1, count($names)), $orders, 'Priority orders should be contiguous starting at 1.');
    }

    private function seedStack(): void
    {
        Bucket::factory()->fixed()->create(['name' => 'Mortgage', 'priority_order' => 1]);
        Bucket::factory()->fixed()->create(['name' => 'Daycare', 'priority_order' => 2]);
        Bucket::factory()->fixed()->create(['name' => 'Hydro', 'priority_order' => 3]);
    }

    public function test_creating_at_an_occupied_priority_pushes_the_rest_down(): void
    {
        $this->seedStack();

        $this->post(route('buckets.store'), [
            'name' => 'Rent',
            'type' => Bucket::TYPE_FIXED,
            'monthly_target' => '1500',
            'priority_order' => 2,
        ])->assertRedirect(route('buckets.index'));

        $this->assertStackIs(['Mortgage', 'Rent', 'Daycare', 'Hydro']);
    }

    public function test_creating_at_the_top_pushes_everything_down(): void
    {
        $this->seedStack();

        $this->post(route('buckets.store'), [
            'name' => 'Rent',
            'type' => Bucket::TYPE_FIXED,
            'monthly_target' => '1500',
            'priority_order' => 1,
        ]);

        $this->assertStackIs(['Rent', 'Mortgage', 'Daycare', 'Hydro']);
    }

    public function test_creating_without_a_priority_appends_to_the_end(): void
    {
        $this->seedStack();

        $this->post(route('buckets.store'), [
            'name' => 'Rent',
            'type' => Bucket::TYPE_FIXED,
            'monthly_target' => '1500',
            'priority_order' => null,
        ]);

        $this->assertStackIs(['Mortgage', 'Daycare', 'Hydro', 'Rent']);
    }

    public function test_creating_beyond_the_end_clamps_to_the_end(): void
    {
        $this->seedStack();

        $this->post(route('buckets.store'), [
            'name' => 'Rent',
            'type' => Bucket::TYPE_FIXED,
            'monthly_target' => '1500',
            'priority_order' => 99,
        ]);

        $this->assertStackIs(['Mortgage', 'Daycare', 'Hydro', 'Rent']);
    }

    public function test_creating_at_zero_clamps_to_the_top(): void
    {
        $this->seedStack();

        $this->post(route('buckets.store'), [
            'name' => 'Rent',
            'type' => Bucket::TYPE_FIXED,
            'monthly_target' => '1500',
            'priority_order' => 0,
        ]);

        $this->assertStackIs(['Rent', 'Mortgage', 'Daycare', 'Hydro']);
    }

    public function test_excess_buckets_do_not_take_a_priority_slot(): void
    {
        $this->seedStack();

        $this->post(route('buckets.store'), [
            'name' => 'Savings',
            'type' => Bucket::TYPE_EXCESS,
            'excess_percentage' => 100,
            'priority_order' => 2,
        ]);

        $this->assertStackIs(['Mortgage', 'Daycare', 'Hydro']);
        $this->assertNull(Bucket::where('name', 'Savings')->value('priority_order'));
    }

    public function test_moving_a_bucket_up_shifts_the_ones_it_passes_down(): void
    {
        $this->seedStack();
        $hydro = Bucket::where('name', 'Hydro')->first();

        $this->put(route('buckets.update', $hydro), [
            'name' => 'Hydro',
            'type' => Bucket::TYPE_FIXED,
            'monthly_target' => '150',
            'priority_order' => 1,
        ])->assertRedirect(route('buckets.index'));

        $this->assertStackIs(['Hydro', 'Mortgage', 'Daycare']);
    }

    public function test_moving_a_bucket_down_shifts_the_ones_it_passes_up(): void
    {
        $this->seedStack();
        $mortgage = Bucket::where('name', 'Mortgage')->first();

        $this->put(route('buckets.update', $mortgage), [
            'name' => 'Mortgage',
            'type' => Bucket::TYPE_FIXED,
            'monthly_target' => '2000',
            'priority_order' => 3,
        ]);

        $this->assertStackIs(['Daycare', 'Hydro', 'Mortgage']);
    }

    public function test_updating_without_touching_priority_leaves_the_stack_alone(): void
    {
        $this->seedStack();
        $daycare = Bucket::where('name', 'Daycare')->first();

        $this->put(route('buckets.update', $daycare), [
            'name' => 'Daycare',
            'type' => Bucket::TYPE_FIXED,
            'monthly_target' => '900',
            'priority_order' => 2,
        ]);

        $this->assertStackIs(['Mortgage', 'Daycare', 'Hydro']);
        $this->assertSame(90000, Bucket::where('name', 'Daycare')->value('monthly_target'));
    }

    public function test_deleting_a_bucket_closes_the_gap(): void
    {
        $this->seedStack();
        $daycare = Bucket::where('name', 'Daycare')->first();

        $this->delete(route('buckets.destroy', $daycare));

        $this->assertStackIs(['Mortgage', 'Hydro']);
    }

    public function test_priority_submitted_as_a_form_string_is_accepted(): void
    {
        $this->seedStack();

        // A real browser form posts every field as a string.
        $this->post(route('buckets.store'), [
            'name' => 'Rent',
            'type' => Bucket::TYPE_FIXED,
            'monthly_target' => '1500',
            'priority_order' => '2',
        ])->assertRedirect(route('buckets.index'));

        $this->assertStackIs(['Mortgage', 'Rent', 'Daycare', 'Hydro']);
    }

    public function test_editing_a_bucket_with_string_form_input_keeps_its_slot(): void
    {
        $this->seedStack();
        $daycare = Bucket::where('name', 'Daycare')->first();

        $this->put(route('buckets.update', $daycare), [
            'name' => 'Daycare',
            'type' => Bucket::TYPE_FIXED,
            'monthly_target' => '925.50',
            'priority_order' => '2',
        ])->assertRedirect(route('buckets.index'));

        $this->assertStackIs(['Mortgage', 'Daycare', 'Hydro']);
        $this->assertSame(92550, Bucket::where('name', 'Daycare')->value('monthly_target'));
    }

    public function test_non_numeric_priority_is_rejected(): void
    {
        $this->seedStack();

        $this->post(route('buckets.store'), [
            'name' => 'Rent',
            'type' => Bucket::TYPE_FIXED,
            'monthly_target' => '1500',
            'priority_order' => 'abc',
        ])->assertSessionHasErrors('priority_order');

        $this->assertStackIs(['Mortgage', 'Daycare', 'Hydro']);
    }

    public function test_switching_a_fixed_bucket_to_excess_frees_its_slot(): void
    {
        $this->seedStack();
        $daycare = Bucket::where('name', 'Daycare')->first();

        $this->put(route('buckets.update', $daycare), [
            'name' => 'Daycare',
            'type' => Bucket::TYPE_EXCESS,
            'excess_percentage' => 50,
            'priority_order' => 2,
        ]);

        $this->assertStackIs(['Mortgage', 'Hydro']);
        $this->assertNull(Bucket::where('name', 'Daycare')->value('priority_order'));
    }
}
