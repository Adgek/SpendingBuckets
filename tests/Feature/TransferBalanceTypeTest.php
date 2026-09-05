<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferBalanceTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_defaults_to_total_balance_type(): void
    {
        $source = Bucket::factory()->fixed()->create(['priority_order' => 1, 'monthly_target' => 100000]);
        $dest = Bucket::factory()->fixed()->create(['priority_order' => 2, 'monthly_target' => 100000]);

        Transaction::factory()->create([
            'bucket_id' => $source->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_ALLOCATION,
        ]);

        $response = $this->post(route('transfers.store'), [
            'source_bucket_id' => $source->id,
            'destination_bucket_id' => $dest->id,
            'amount' => '100.00',
            'balance_type' => 'total',
        ]);

        $response->assertRedirect(route('buckets.index'));

        $transfers = Transaction::where('type', Transaction::TYPE_TRANSFER)->get();
        $this->assertCount(2, $transfers);

        // Both should have balance_type = total
        $this->assertTrue($transfers->every(fn ($t) => $t->balance_type === 'total'));
    }

    public function test_monthly_transfer_stores_balance_type_monthly(): void
    {
        Carbon::setTestNow('2026-04-15');

        $source = Bucket::factory()->fixed()->create(['priority_order' => 1, 'monthly_target' => 100000]);
        $dest = Bucket::factory()->fixed()->create(['priority_order' => 2, 'monthly_target' => 100000]);

        $deposit = Deposit::factory()->create(['deposit_date' => '2026-04-01', 'amount' => 100000]);
        Transaction::factory()->create([
            'bucket_id' => $source->id,
            'deposit_id' => $deposit->id,
            'amount' => 100000,
            'type' => Transaction::TYPE_ALLOCATION,
            'created_at' => '2026-04-01',
        ]);

        $response = $this->post(route('transfers.store'), [
            'source_bucket_id' => $source->id,
            'destination_bucket_id' => $dest->id,
            'amount' => '200.00',
            'balance_type' => 'monthly',
        ]);

        $response->assertRedirect(route('buckets.index'));

        $transfers = Transaction::where('type', Transaction::TYPE_TRANSFER)->get();
        $this->assertCount(2, $transfers);
        $this->assertTrue($transfers->every(fn ($t) => $t->balance_type === 'monthly'));

        Carbon::setTestNow();
    }

    public function test_transfer_form_shows_balance_type_selector(): void
    {
        Bucket::factory()->fixed()->create(['priority_order' => 1]);

        $response = $this->get(route('buckets.index'));

        $response->assertOk();
        $response->assertSee('balance_type');
        $response->assertSee('Monthly Balance');
        $response->assertSee('Total Balance');
    }
}
