<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bucket;
use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transaction> */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'bucket_id' => Bucket::factory(),
            'deposit_id' => Deposit::factory(),
            'amount' => fake()->numberBetween(1000, 100000),
            'type' => 'allocation',
            'reference_id' => null,
            'description' => fake()->sentence(),
        ];
    }
}
