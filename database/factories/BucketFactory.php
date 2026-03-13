<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bucket;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Bucket> */
class BucketFactory extends Factory
{
    protected $model = Bucket::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'type' => 'fixed',
            'monthly_target' => fake()->numberBetween(10000, 200000),
            'priority_order' => fake()->numberBetween(1, 10),
            'cap' => null,
            'sweeps_excess' => false,
            'receives_sweeps' => false,
            'excess_percentage' => null,
            'is_primary_savings' => false,
        ];
    }

    public function fixed(): static
    {
        return $this->state(fn () => [
            'type' => 'fixed',
            'excess_percentage' => null,
        ]);
    }

    public function excess(): static
    {
        return $this->state(fn () => [
            'type' => 'excess',
            'monthly_target' => null,
            'priority_order' => null,
            'excess_percentage' => fake()->numberBetween(10, 50),
        ]);
    }

    public function primarySavings(): static
    {
        return $this->excess()->state(fn () => [
            'is_primary_savings' => true,
        ]);
    }
}
