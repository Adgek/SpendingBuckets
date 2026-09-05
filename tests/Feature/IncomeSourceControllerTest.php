<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IncomeSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeSourceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_income_sources(): void
    {
        IncomeSource::factory()->create(['name' => 'Rental Property', 'amount' => 180000]);

        $response = $this->get(route('income-sources.index'));

        $response->assertOk();
        $response->assertViewIs('income-sources.index');
        $response->assertViewHas('incomeSources');
        $response->assertSee('Rental Property');
        $response->assertSee('1,800.00');
    }

    public function test_create_displays_form(): void
    {
        $response = $this->get(route('income-sources.create'));

        $response->assertOk();
        $response->assertViewIs('income-sources.create');
    }

    public function test_edit_displays_form_with_current_values(): void
    {
        $source = IncomeSource::factory()->create(['name' => 'Rental Property', 'amount' => 180050]);

        $response = $this->get(route('income-sources.edit', $source));

        $response->assertOk();
        $response->assertViewIs('income-sources.edit');
        $response->assertSee('Rental Property');
        $response->assertSee('1800.50');
    }

    public function test_store_converts_dollars_to_cents(): void
    {
        $response = $this->post(route('income-sources.store'), [
            'name' => 'Rental Property',
            'amount' => '1800.50',
            'description' => 'Old house',
        ]);

        $response->assertRedirect(route('income-sources.index'));
        $this->assertDatabaseHas('income_sources', [
            'name' => 'Rental Property',
            'amount' => 180050,
            'description' => 'Old house',
            'is_active' => true,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->post(route('income-sources.store'), []);

        $response->assertSessionHasErrors(['name', 'amount']);
    }

    public function test_store_rejects_non_positive_amount(): void
    {
        $response = $this->post(route('income-sources.store'), [
            'name' => 'Bad',
            'amount' => '0',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_update_changes_amount(): void
    {
        $source = IncomeSource::factory()->create(['amount' => 100000]);

        $response = $this->put(route('income-sources.update', $source), [
            'name' => 'Rental Property',
            'amount' => '2000',
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('income-sources.index'));
        $this->assertDatabaseHas('income_sources', [
            'id' => $source->id,
            'name' => 'Rental Property',
            'amount' => 200000,
            'is_active' => false,
        ]);
    }

    public function test_destroy_removes_income_source(): void
    {
        $source = IncomeSource::factory()->create();

        $response = $this->delete(route('income-sources.destroy', $source));

        $response->assertRedirect(route('income-sources.index'));
        $this->assertDatabaseMissing('income_sources', ['id' => $source->id]);
    }

    public function test_monthly_total_only_counts_active_sources(): void
    {
        IncomeSource::factory()->create(['amount' => 100000, 'is_active' => true]);
        IncomeSource::factory()->create(['amount' => 50000, 'is_active' => true]);
        IncomeSource::factory()->create(['amount' => 999900, 'is_active' => false]);

        $this->assertSame(150000, IncomeSource::monthlyTotal());
    }
}
