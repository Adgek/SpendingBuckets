<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Deposit;
use App\Models\Period;
use App\Services\ActivePeriodService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivePeriodServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivePeriodService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ActivePeriodService();
    }

    public function test_active_period_is_current_calendar_month_when_no_data_exists(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1));

        $period = $this->service->current();

        $this->assertEquals('2026-04-01', $period->format('Y-m-d'));
    }

    public function test_active_period_is_first_deposit_month_when_unclosed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1));

        Deposit::factory()->create(['deposit_date' => Carbon::create(2026, 2, 15)]);

        $period = $this->service->current();

        // First deposit was in February, and it's never been closed
        $this->assertEquals('2026-02-01', $period->format('Y-m-d'));
    }

    public function test_active_period_advances_when_period_is_closed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1));

        Deposit::factory()->create(['deposit_date' => Carbon::create(2026, 2, 15)]);

        // Close February
        Period::create([
            'month' => Carbon::create(2026, 2, 1),
            'closed_at' => Carbon::create(2026, 3, 1),
        ]);

        $period = $this->service->current();

        // February is closed, so active period should be March
        $this->assertEquals('2026-03-01', $period->format('Y-m-d'));
    }

    public function test_active_period_stays_on_unclosed_month_even_when_calendar_advances(): void
    {
        // Deposit in March, it's now May — March was never closed
        Carbon::setTestNow(Carbon::create(2026, 5, 15));

        Deposit::factory()->create(['deposit_date' => Carbon::create(2026, 3, 10)]);

        $period = $this->service->current();

        $this->assertEquals('2026-03-01', $period->format('Y-m-d'));
    }

    public function test_active_period_never_exceeds_current_calendar_month(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 15));

        Deposit::factory()->create(['deposit_date' => Carbon::create(2026, 3, 10)]);

        // Close March and April
        Period::create(['month' => Carbon::create(2026, 3, 1), 'closed_at' => now()]);
        Period::create(['month' => Carbon::create(2026, 4, 1), 'closed_at' => now()]);

        $period = $this->service->current();

        // Should cap at current calendar month
        $this->assertTrue($period->lte(Carbon::now()->startOfMonth()));
    }

    public function test_closed_periods_returns_only_closed_months(): void
    {
        Period::create(['month' => Carbon::create(2026, 1, 1), 'closed_at' => now()]);
        Period::create(['month' => Carbon::create(2026, 2, 1), 'closed_at' => now()]);
        Period::create(['month' => Carbon::create(2026, 3, 1), 'closed_at' => null]);

        Carbon::setTestNow(Carbon::create(2026, 4, 1));

        $closed = $this->service->closedPeriods();

        $this->assertCount(2, $closed);
        $this->assertEquals('2026-02-01', $closed[0]->month->format('Y-m-d'));
        $this->assertEquals('2026-01-01', $closed[1]->month->format('Y-m-d'));
    }

    public function test_ensure_periods_creates_records_from_first_deposit_through_current_month(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1));

        Deposit::factory()->create(['deposit_date' => Carbon::create(2026, 2, 15)]);

        $this->service->ensurePeriods();

        $months = Period::pluck('month')->map(fn ($m) => $m->format('Y-m'))->toArray();
        $this->assertContains('2026-02', $months);
        $this->assertContains('2026-03', $months);
        $this->assertContains('2026-04', $months);
        $this->assertEquals(3, Period::count());
    }
}
