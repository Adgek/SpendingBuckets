<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\IncomeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<IncomeSource> */
class IncomeSourceFactory extends Factory
{
    protected $model = IncomeSource::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'amount' => fake()->numberBetween(10000, 300000),
            'description' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
