<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RunSweepAction;
use App\Models\Bucket;
use App\Models\Period;
use App\Models\PeriodBucketSnapshot;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SweepDateTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Bucket, 1: Bucket} */
    private function makeBuckets(): array
    {
        $savings = Bucket::factory()->primarySavings()->create(['name' => 'Savings']);

        $hydro = Bucket::factory()->fixed()->create([
            'name' => 'Hydro',
            'monthly_target' => 22000,
            'priority_order' => 1,
            'sweeps_excess' => true,
        ]);

        Transaction::factory()->create([
            'bucket_id' => $hydro->id,
            'amount' => 20000,
            'type' => Transaction::TYPE_ALLOCATION,
            'created_at' => '2026-07-15 23:59:59',
        ]);

        return [$savings, $hydro];
    }

    public function test_sweep_transactions_are_dated_the_last_day_of_the_swept_month(): void
    {
        Carbon::setTestNow('2026-08-25 22:16:10');
        $this->makeBuckets();

        app(RunSweepAction::class)->execute('2026-07');

        $sweeps = Transaction::where('type', Transaction::TYPE_SWEEP)->get();

        $this->assertCount(2, $sweeps);

        foreach ($sweeps as $sweep) {
            $this->assertSame(
                '2026-07-31 23:59:59',
                $sweep->created_at->format('Y-m-d H:i:s'),
                'Sweep transactions must land on the last day of the month being closed.'
            );
        }
    }

    public function test_snapshot_records_the_sweep_even_when_run_in_a_later_month(): void
    {
        Carbon::setTestNow('2026-08-25 22:16:10');
        [, $hydro] = $this->makeBuckets();

        app(RunSweepAction::class)->execute('2026-07');

        $period = Period::whereDate('month', '2026-07-01')->firstOrFail();
        $snapshot = PeriodBucketSnapshot::where('period_id', $period->id)
            ->where('bucket_id', $hydro->id)
            ->firstOrFail();

        $this->assertSame(20000, $snapshot->swept);
        $this->assertSame(0, $snapshot->closing_balance);
    }

    public function test_sweep_event_shows_up_on_the_period_it_closed(): void
    {
        Carbon::setTestNow('2026-08-25 22:16:10');
        $this->makeBuckets();

        app(RunSweepAction::class)->execute('2026-07');

        $july = Period::whereDate('month', '2026-07-01')->firstOrFail();
        $events = $july->sweepEvents();

        $this->assertCount(1, $events);
        $this->assertSame(20000, $events[0]['total']);
    }
}
