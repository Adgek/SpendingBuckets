<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bucket;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopUpBucketTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function savings(int $balance = 500000): Bucket
    {
        $savings = Bucket::factory()->primarySavings()->create(['name' => 'Savings']);

        if ($balance !== 0) {
            Transaction::factory()->create([
                'deposit_id' => null,
                'bucket_id' => $savings->id,
                'amount' => $balance,
                'type' => Transaction::TYPE_ALLOCATION,
            ]);
        }

        return $savings;
    }

    private function bucketWithBalance(string $name, int $balance): Bucket
    {
        $bucket = Bucket::factory()->fixed()->create(['name' => $name]);

        if ($balance !== 0) {
            Transaction::factory()->create([
                'deposit_id' => null,
                'bucket_id' => $bucket->id,
                'amount' => $balance,
                'type' => $balance < 0 ? Transaction::TYPE_EXPENSE : Transaction::TYPE_ALLOCATION,
            ]);
        }

        return $bucket;
    }

    public function test_topping_up_brings_a_negative_bucket_to_zero(): void
    {
        $savings = $this->savings(500000);
        $hydro = $this->bucketWithBalance('Hydro', -12500);

        $response = $this->from(route('buckets.show', $hydro))
            ->post(route('buckets.top-up', $hydro));

        $response->assertRedirect(route('buckets.show', $hydro));
        $response->assertSessionHas('success');

        $this->assertSame(0, $hydro->fresh()->balance);
        $this->assertSame(500000 - 12500, $savings->fresh()->balance);
    }

    public function test_top_up_is_recorded_as_a_paired_transfer(): void
    {
        $savings = $this->savings();
        $hydro = $this->bucketWithBalance('Hydro', -12500);

        $this->post(route('buckets.top-up', $hydro));

        $legs = Transaction::where('type', Transaction::TYPE_TRANSFER)->get();

        $this->assertCount(2, $legs);
        $this->assertCount(1, $legs->pluck('reference_id')->unique());
        $this->assertNotNull($legs->first()->reference_id);

        $out = $legs->firstWhere('bucket_id', $savings->id);
        $in = $legs->firstWhere('bucket_id', $hydro->id);

        $this->assertSame(-12500, $out->amount);
        $this->assertSame(12500, $in->amount);
        $this->assertSame(Transaction::BALANCE_TOTAL, $in->balance_type);
        $this->assertStringContainsString('Hydro', (string) $in->description);
        $this->assertStringContainsString('Savings', (string) $in->description);
    }

    public function test_top_up_shows_in_the_activity_feed_as_a_transfer(): void
    {
        $this->savings();
        $hydro = $this->bucketWithBalance('Hydro', -12500);

        $this->post(route('buckets.top-up', $hydro));

        $response = $this->get(route('deposits.index', ['type' => 'transfers']));

        $response->assertOk();
        $response->assertSee('Savings');
        $response->assertSee('Hydro');
        $response->assertSee('125.00');
    }

    public function test_top_up_is_dated_to_the_end_of_last_month(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 12, 2, 9, 30));
        $this->savings();
        $hydro = $this->bucketWithBalance('Hydro', -12500);

        $this->post(route('buckets.top-up', $hydro));

        $legs = Transaction::where('type', Transaction::TYPE_TRANSFER)->get();

        $this->assertCount(2, $legs);
        foreach ($legs as $leg) {
            $this->assertSame('2026-11-30 23:59:59', $leg->created_at->format('Y-m-d H:i:s'));
        }
    }

    public function test_top_up_on_the_last_day_of_a_long_month_lands_in_the_month_before(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 31, 9, 30));
        $this->savings();
        $hydro = $this->bucketWithBalance('Hydro', -12500);

        $this->post(route('buckets.top-up', $hydro));

        $leg = Transaction::where('type', Transaction::TYPE_TRANSFER)->firstOrFail();

        $this->assertSame('2026-02-28 23:59:59', $leg->created_at->format('Y-m-d H:i:s'));
    }

    public function test_top_up_leaves_the_new_months_balance_untouched(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 12, 2, 9, 30));
        $savings = $this->savings();
        $hydro = $this->bucketWithBalance('Hydro', -12500);
        Transaction::query()->update(['created_at' => '2026-11-15 12:00:00']);

        $this->post(route('buckets.top-up', $hydro));

        $december = Carbon::create(2026, 12, 1);

        $this->assertSame(0, $hydro->fresh()->monthlyBalance($december));
        $this->assertSame(0, $savings->fresh()->monthlyBalance($december));
    }

    public function test_a_bucket_already_in_the_black_cannot_be_topped_up(): void
    {
        $this->savings();
        $hydro = $this->bucketWithBalance('Hydro', 5000);

        $response = $this->from(route('buckets.show', $hydro))
            ->post(route('buckets.top-up', $hydro));

        $response->assertRedirect(route('buckets.show', $hydro));
        $response->assertSessionHas('error');
        $this->assertSame(0, Transaction::where('type', Transaction::TYPE_TRANSFER)->count());
    }

    public function test_a_bucket_at_zero_cannot_be_topped_up(): void
    {
        $this->savings();
        $hydro = $this->bucketWithBalance('Hydro', 0);

        $this->from(route('buckets.show', $hydro))
            ->post(route('buckets.top-up', $hydro))
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::where('type', Transaction::TYPE_TRANSFER)->count());
    }

    public function test_top_up_requires_a_primary_savings_bucket(): void
    {
        $hydro = $this->bucketWithBalance('Hydro', -12500);

        $this->from(route('buckets.show', $hydro))
            ->post(route('buckets.top-up', $hydro))
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::where('type', Transaction::TYPE_TRANSFER)->count());
    }

    public function test_savings_cannot_top_itself_up(): void
    {
        $savings = Bucket::factory()->primarySavings()->create(['name' => 'Savings']);
        Transaction::factory()->create([
            'deposit_id' => null,
            'bucket_id' => $savings->id,
            'amount' => -20000,
            'type' => Transaction::TYPE_EXPENSE,
        ]);

        $this->from(route('buckets.show', $savings))
            ->post(route('buckets.top-up', $savings))
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::where('type', Transaction::TYPE_TRANSFER)->count());
    }

    public function test_sweep_page_lists_buckets_in_the_red(): void
    {
        $this->savings();
        $this->bucketWithBalance('Hydro', -12500);
        $this->bucketWithBalance('Water', -3000);
        $this->bucketWithBalance('Rent', 20000);

        $response = $this->get(route('sweep.create'));

        $response->assertOk();
        $response->assertViewHas('negativeBuckets');
        $response->assertSee('Hydro');
        $response->assertSee('Water');
        $response->assertSee('125.00');
        $response->assertSee('30.00');
        $response->assertSee('Make Whole');
    }

    public function test_sweep_page_says_nothing_when_no_bucket_is_negative(): void
    {
        $this->savings();
        $this->bucketWithBalance('Rent', 20000);

        $response = $this->get(route('sweep.create'));

        $response->assertOk();
        $response->assertViewHas('negativeBuckets', fn ($buckets) => $buckets->isEmpty());
        $response->assertDontSee('Make Whole');
    }

    public function test_topping_up_from_the_sweep_page_returns_there(): void
    {
        $this->savings();
        $hydro = $this->bucketWithBalance('Hydro', -12500);

        $this->from(route('sweep.create'))
            ->post(route('buckets.top-up', $hydro))
            ->assertRedirect(route('sweep.create'));

        $this->assertSame(0, $hydro->fresh()->balance);
    }

    public function test_bucket_page_offers_the_button_only_when_negative(): void
    {
        $this->savings();
        $hydro = $this->bucketWithBalance('Hydro', -12500);
        $rent = $this->bucketWithBalance('Rent', 20000);

        $this->get(route('buckets.show', $hydro))->assertSee('Make Whole');
        $this->get(route('buckets.show', $rent))->assertDontSee('Make Whole');
    }
}
