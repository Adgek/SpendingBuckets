<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Deposit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Deposit> */
class DepositFactory extends Factory
{
    protected $model = Deposit::class;

    public function definition(): array
    {
        return [
            'amount' => fake()->numberBetween(100000, 1000000),
            'deposit_date' => fake()->date(),
            'description' => fake()->sentence(),
        ];
    }
}
