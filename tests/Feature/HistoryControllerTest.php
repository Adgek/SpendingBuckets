<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Period;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class HistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_page_renders_sweep_audit_trail_for_closed_period(): void
    {
        $hydro = Bucket::factory()->fixed()->create(['name' => 'Hydro', 'priority_order' => 1]);
        $water = Bucket::factory()->fixed()->create(['name' => 'Water', 'priority_order' => 2]);
        $emergency = Bucket::factory()->create(['name' => 'Emergency Fund', 'priority_order' => 3]);
        $savings = Bucket::factory()->primarySavings()->create(['name' => 'Savings', 'priority_order' => 4]);

        $period = Period::create([
            'month' => '2026-04-01',
            'closed_at' => Carbon::parse('2026-04-30 20:13:33'),
            'closing_balance' => 100000,
        ]);

        $sweepRef = (string) Str::uuid();
        $sweptAt = Carbon::parse('2026-04-30 20:13:33');

        // Two sources drained
        $this->makeSweepTxn($hydro->id, -3611, $sweepRef, $sweptAt, 'End-of-month sweep from Hydro');
        $this->makeSweepTxn($water->id, -1818, $sweepRef, $sweptAt, 'End-of-month sweep from Water');

        // Two destinations: emergency fund partially, savings remainder
        $this->makeSweepTxn($emergency->id, 4000, $sweepRef, $sweptAt, 'Sweep receive into Emergency Fund');
        $this->makeSweepTxn($savings->id, 1429, $sweepRef, $sweptAt, 'Sweep remainder to primary savings');

        $response = $this->get(route('history.index'));

        $response->assertOk();
        $response->assertViewIs('history.index');

        // Sources visible with amounts
        $response->assertSeeText('Hydro');
        $response->assertSeeText('$36.11');
        $response->assertSeeText('Water');
        $response->assertSeeText('$18.18');

        // Destinations visible with amounts
        $response->assertSeeText('Emergency Fund');
        $response->assertSeeText('$40.00');
        $response->assertSeeText('Savings');
        $response->assertSeeText('$14.29');

        // Section heading
        $response->assertSeeText('Sweep Audit');
    }

    private function makeSweepTxn(int $bucketId, int $amount, string $refId, Carbon $when, string $description): void
    {
        $txn = new Transaction([
            'bucket_id' => $bucketId,
            'amount' => $amount,
            'type' => Transaction::TYPE_SWEEP,
            'reference_id' => $refId,
            'description' => $description,
        ]);
        $txn->timestamps = false;
        $txn->created_at = $when;
        $txn->save();
    }
}
