<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_current_month_totals_and_per_paycheck(): void
    {
        $rent = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 200000, // $2,000
            'priority_order' => 1,
        ]);

        $groceries = Bucket::factory()->fixed()->create([
            'name' => 'Groceries',
            'monthly_target' => 60000, // $600
            'priority_order' => 2,
        ]);

        // Current month allocation
        $currentDeposit = Deposit::factory()->create(['deposit_date' => now()]);
        Transaction::factory()->create([
            'bucket_id' => $rent->id,
            'deposit_id' => $currentDeposit->id,
            'amount' => 100000, // $1,000 funded so far
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard');
        $response->assertViewHas('currentMonthLabel');
        $response->assertViewHas('totalMonthlyTarget');
        $response->assertViewHas('totalFundedThisMonth');
        $response->assertViewHas('perPaycheck');
        $response->assertViewHas('lastMonthLabel');
        $response->assertViewHas('totalFundedLastMonth');
    }

    public function test_dashboard_calculates_per_paycheck_as_target_divided_by_four(): void
    {
        Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 200000, // $2,000
            'priority_order' => 1,
        ]);

        Bucket::factory()->fixed()->create([
            'name' => 'Water',
            'monthly_target' => 5000, // $50
            'priority_order' => 2,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        // Total target = $2,050 = 205000 cents, per paycheck = 51250 cents
        $response->assertViewHas('totalMonthlyTarget', 205000);
        $response->assertViewHas('perPaycheck', 51250);
    }

    public function test_dashboard_shows_last_month_funded_amount(): void
    {
        $bucket = Bucket::factory()->fixed()->create([
            'name' => 'Rent',
            'monthly_target' => 200000,
            'priority_order' => 1,
        ]);

        // Last month allocation
        $lastMonthDeposit = Deposit::factory()->create(['deposit_date' => now()->subMonth()]);
        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $lastMonthDeposit->id,
            'amount' => 150000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        // Current month allocation
        $currentMonthDeposit = Deposit::factory()->create(['deposit_date' => now()]);
        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $currentMonthDeposit->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('totalFundedLastMonth', 150000);
        $response->assertViewHas('totalFundedThisMonth', 50000);
    }
}
