<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_a_deposit(): void
    {
        $deposit = Deposit::factory()->create([
            'amount' => 500000,
            'deposit_date' => '2025-07-15',
            'description' => 'July Paycheck',
        ]);

        $this->assertDatabaseHas('deposits', [
            'id' => $deposit->id,
            'amount' => 500000,
            'description' => 'July Paycheck',
        ]);

        $this->assertTrue($deposit->deposit_date->equalTo('2025-07-15'));
    }

    public function test_deposit_has_many_transactions(): void
    {
        $deposit = Deposit::factory()->create();

        Transaction::factory()->count(2)->create([
            'deposit_id' => $deposit->id,
        ]);

        $this->assertCount(2, $deposit->transactions);
    }
}
