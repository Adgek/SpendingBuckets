<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Bucket;
use Illuminate\Database\Seeder;

class SnapshotSeeder extends Seeder
{
    /**
     * Seed the database with sample bucket data for development.
     */
    public function run(): void
    {
        $fixedBuckets = [
            ['name' => 'Rent',           'monthly_target' => 180000, 'priority_order' => 1,  'sweeps_excess' => false],
            ['name' => 'Childcare',      'monthly_target' => 150000, 'priority_order' => 2,  'sweeps_excess' => true],
            ['name' => 'Home Insurance',  'monthly_target' => 22000,  'priority_order' => 3,  'sweeps_excess' => true],
            ['name' => 'Utilities',      'monthly_target' => 20000,  'priority_order' => 4,  'sweeps_excess' => true],
            ['name' => 'Groceries',      'monthly_target' => 60000,  'priority_order' => 5,  'sweeps_excess' => true],
            ['name' => 'Internet',       'monthly_target' => 7500,   'priority_order' => 6,  'sweeps_excess' => true],
            ['name' => 'Cell Phone',     'monthly_target' => 12000,  'priority_order' => 7,  'sweeps_excess' => true],
            ['name' => 'Car Insurance',  'monthly_target' => 25000,  'priority_order' => 8,  'sweeps_excess' => true],
        ];

        foreach ($fixedBuckets as $bucket) {
            Bucket::create([
                'name' => $bucket['name'],
                'type' => Bucket::TYPE_FIXED,
                'monthly_target' => $bucket['monthly_target'],
                'priority_order' => $bucket['priority_order'],
                'sweeps_excess' => $bucket['sweeps_excess'],
            ]);
        }

        $excessBuckets = [
            ['name' => 'Emergency Fund',   'excess_percentage' => 40, 'cap' => 500000,  'is_primary_savings' => false],
            ['name' => 'Vacation',          'excess_percentage' => 30, 'cap' => 300000,  'is_primary_savings' => false],
            ['name' => 'General Savings',   'excess_percentage' => 30, 'cap' => null,     'is_primary_savings' => true],
        ];

        foreach ($excessBuckets as $bucket) {
            Bucket::create([
                'name' => $bucket['name'],
                'type' => Bucket::TYPE_EXCESS,
                'excess_percentage' => $bucket['excess_percentage'],
                'cap' => $bucket['cap'],
                'is_primary_savings' => $bucket['is_primary_savings'],
            ]);
        }
    }
}
