<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Services\ActivityFeedService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityFeedServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeDeposit(Bucket $bucket, string $date, int $amount = 100000): Deposit
    {
        $deposit = Deposit::factory()->create([
            'deposit_date' => $date,
            'amount' => $amount,
            'description' => 'Paycheck',
        ]);

        Transaction::factory()->create([
            'bucket_id' => $bucket->id,
            'deposit_id' => $deposit->id,
            'amount' => $amount,
            'type' => Transaction::TYPE_ALLOCATION,
            'created_at' => Carbon::parse($date),
        ]);

        return $deposit;
    }

    private function makeExpense(Bucket $bucket, string $date, int $amount = 4500): Transaction
    {
        return Transaction::factory()->create([
            'deposit_id' => null,
            'bucket_id' => $bucket->id,
            'amount' => -$amount,
            'type' => Transaction::TYPE_EXPENSE,
            'description' => 'Water bill',
            'created_at' => Carbon::parse($date),
        ]);
    }

    private function makeTransfer(Bucket $from, Bucket $to, string $date, int $amount = 20000): string
    {
        $reference = Str::uuid()->toString();

        Transaction::factory()->create([
            'deposit_id' => null,
            'bucket_id' => $from->id,
            'amount' => -$amount,
            'type' => Transaction::TYPE_TRANSFER,
            'reference_id' => $reference,
            'description' => 'Car repair',
            'created_at' => Carbon::parse($date),
        ]);

        Transaction::factory()->create([
            'deposit_id' => null,
            'bucket_id' => $to->id,
            'amount' => $amount,
            'type' => Transaction::TYPE_TRANSFER,
            'reference_id' => $reference,
            'description' => 'Car repair',
            'created_at' => Carbon::parse($date),
        ]);

        return $reference;
    }

    private function makeSweep(Bucket $from, Bucket $to, string $date, int $amount = 15000): string
    {
        $reference = Str::uuid()->toString();

        Transaction::factory()->create([
            'deposit_id' => null,
            'bucket_id' => $from->id,
            'amount' => -$amount,
            'type' => Transaction::TYPE_SWEEP,
            'reference_id' => $reference,
            'created_at' => Carbon::parse($date),
        ]);

        Transaction::factory()->create([
            'deposit_id' => null,
            'bucket_id' => $to->id,
            'amount' => $amount,
            'type' => Transaction::TYPE_SWEEP,
            'reference_id' => $reference,
            'created_at' => Carbon::parse($date),
        ]);

        return $reference;
    }

    public function test_all_returns_every_kind_newest_first(): void
    {
        $rent = Bucket::factory()->fixed()->create(['name' => 'Rent']);
        $savings = Bucket::factory()->primarySavings()->create(['name' => 'Savings']);

        $this->makeDeposit($rent, '2026-05-01 09:00:00');
        $this->makeExpense($rent, '2026-05-02 09:00:00');
        $this->makeTransfer($rent, $savings, '2026-05-03 09:00:00');
        $this->makeSweep($rent, $savings, '2026-05-04 09:00:00');

        $entries = app(ActivityFeedService::class)->entries();

        $this->assertCount(4, $entries);
        $this->assertSame(
            ['sweep', 'transfer', 'expense', 'deposit'],
            $entries->pluck('kind')->all()
        );
    }

    public function test_filters_by_kind(): void
    {
        $rent = Bucket::factory()->fixed()->create(['name' => 'Rent']);
        $savings = Bucket::factory()->primarySavings()->create(['name' => 'Savings']);

        $this->makeDeposit($rent, '2026-05-01 09:00:00');
        $this->makeExpense($rent, '2026-05-02 09:00:00');
        $this->makeTransfer($rent, $savings, '2026-05-03 09:00:00');
        $this->makeSweep($rent, $savings, '2026-05-04 09:00:00');

        $service = app(ActivityFeedService::class);

        $this->assertSame(['deposit'], $service->entries('deposits')->pluck('kind')->unique()->all());
        $this->assertSame(['expense'], $service->entries('expenses')->pluck('kind')->unique()->all());
        $this->assertSame(['transfer'], $service->entries('transfers')->pluck('kind')->unique()->all());
        $this->assertSame(['sweep'], $service->entries('sweeps')->pluck('kind')->unique()->all());
    }

    public function test_transfer_entry_pairs_source_and_destination(): void
    {
        $rent = Bucket::factory()->fixed()->create(['name' => 'Rent']);
        $savings = Bucket::factory()->primarySavings()->create(['name' => 'Savings']);
        $this->makeTransfer($rent, $savings, '2026-05-03 09:00:00', 20000);

        $entry = app(ActivityFeedService::class)->entries('transfers')->first();

        $this->assertSame('Rent', $entry['from']);
        $this->assertSame('Savings', $entry['to']);
        $this->assertSame(20000, $entry['amount']);
        $this->assertSame('Car repair', $entry['description']);
    }

    public function test_sweep_entry_summarises_sources_and_destinations(): void
    {
        $rent = Bucket::factory()->fixed()->create(['name' => 'Rent']);
        $groceries = Bucket::factory()->fixed()->create(['name' => 'Groceries']);
        $savings = Bucket::factory()->primarySavings()->create(['name' => 'Savings']);

        $reference = $this->makeSweep($rent, $savings, '2026-05-04 09:00:00', 15000);

        Transaction::factory()->create([
            'deposit_id' => null,
            'bucket_id' => $groceries->id,
            'amount' => -5000,
            'type' => Transaction::TYPE_SWEEP,
            'reference_id' => $reference,
            'created_at' => Carbon::parse('2026-05-04 09:00:00'),
        ]);
        Transaction::factory()->create([
            'deposit_id' => null,
            'bucket_id' => $savings->id,
            'amount' => 5000,
            'type' => Transaction::TYPE_SWEEP,
            'reference_id' => $reference,
            'created_at' => Carbon::parse('2026-05-04 09:00:00'),
        ]);

        $entry = app(ActivityFeedService::class)->entries('sweeps')->first();

        $this->assertSame(20000, $entry['amount']);
        $this->assertCount(2, $entry['sources']);
        $this->assertCount(1, $entry['destinations']);
        $this->assertSame(20000, $entry['destinations']->first()['amount']);
    }

    public function test_deposit_entry_carries_its_allocation_breakdown(): void
    {
        $rent = Bucket::factory()->fixed()->create(['name' => 'Rent']);
        $this->makeDeposit($rent, '2026-05-01 09:00:00', 100000);

        $entry = app(ActivityFeedService::class)->entries('deposits')->first();

        $this->assertSame(100000, $entry['amount']);
        $this->assertCount(1, $entry['transactions']);
        $this->assertSame('Rent', $entry['transactions']->first()->bucket->name);
    }

    public function test_limit_caps_the_number_of_entries(): void
    {
        $rent = Bucket::factory()->fixed()->create(['name' => 'Rent']);

        $this->makeExpense($rent, '2026-05-01 09:00:00');
        $this->makeExpense($rent, '2026-05-02 09:00:00');
        $this->makeExpense($rent, '2026-05-03 09:00:00');

        $entries = app(ActivityFeedService::class)->entries('all', 2);

        $this->assertCount(2, $entries);
    }

    public function test_unpaired_legacy_transfers_are_not_merged(): void
    {
        $rent = Bucket::factory()->fixed()->create(['name' => 'Rent']);
        $savings = Bucket::factory()->primarySavings()->create(['name' => 'Savings']);

        Transaction::factory()->create([
            'deposit_id' => null,
            'bucket_id' => $rent->id,
            'amount' => -10000,
            'type' => Transaction::TYPE_TRANSFER,
            'reference_id' => null,
            'created_at' => Carbon::parse('2026-05-01 09:00:00'),
        ]);
        Transaction::factory()->create([
            'deposit_id' => null,
            'bucket_id' => $savings->id,
            'amount' => 10000,
            'type' => Transaction::TYPE_TRANSFER,
            'reference_id' => null,
            'created_at' => Carbon::parse('2026-05-02 09:00:00'),
        ]);

        $entries = app(ActivityFeedService::class)->entries('transfers');

        $this->assertCount(2, $entries);
    }
}
