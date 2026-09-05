<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\IncomeSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtherIncomeSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_buckets_page_exposes_other_income_and_net_per_paycheck(): void
    {
        Bucket::factory()->fixed()->create(['monthly_target' => 200000, 'priority_order' => 1]);
        IncomeSource::factory()->create(['amount' => 80000, 'is_active' => true]);

        $response = $this->get(route('buckets.index'));

        $response->assertOk();
        $response->assertViewHas('totalMonthlyTarget', 200000);
        $response->assertViewHas('otherIncome', 80000);
        // (2000 - 800) / 4 = 300
        $response->assertViewHas('perPaycheck', 30000);
    }

    public function test_buckets_page_per_paycheck_unchanged_without_other_income(): void
    {
        Bucket::factory()->fixed()->create(['monthly_target' => 200000, 'priority_order' => 1]);

        $response = $this->get(route('buckets.index'));

        $response->assertViewHas('otherIncome', 0);
        $response->assertViewHas('perPaycheck', 50000);
    }

    public function test_buckets_page_never_shows_negative_per_paycheck(): void
    {
        Bucket::factory()->fixed()->create(['monthly_target' => 50000, 'priority_order' => 1]);
        IncomeSource::factory()->create(['amount' => 200000, 'is_active' => true]);

        $response = $this->get(route('buckets.index'));

        $response->assertViewHas('perPaycheck', 0);
    }

    public function test_buckets_page_displays_the_three_summary_figures(): void
    {
        Bucket::factory()->fixed()->create(['monthly_target' => 200000, 'priority_order' => 1]);
        IncomeSource::factory()->create(['name' => 'Rental Property', 'amount' => 80000]);

        $response = $this->get(route('buckets.index'));

        $response->assertSee('Total / Month');
        $response->assertSee('Other Income');
        $response->assertSee('Each Paycheck', false);
        $response->assertSee('2,000.00');
        $response->assertSee('800.00');
        $response->assertSee('300.00');
    }

    public function test_dashboard_accounts_for_other_income(): void
    {
        Bucket::factory()->fixed()->create(['monthly_target' => 200000, 'priority_order' => 1]);
        IncomeSource::factory()->create(['amount' => 80000]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('otherIncome', 80000);
        $response->assertViewHas('perPaycheck', 30000);
    }

    public function test_inactive_income_is_excluded_from_summaries(): void
    {
        Bucket::factory()->fixed()->create(['monthly_target' => 200000, 'priority_order' => 1]);
        IncomeSource::factory()->create(['amount' => 80000, 'is_active' => false]);

        $response = $this->get(route('buckets.index'));

        $response->assertViewHas('otherIncome', 0);
        $response->assertViewHas('perPaycheck', 50000);
    }
}
