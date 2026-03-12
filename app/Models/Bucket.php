<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bucket extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_FIXED = 'fixed';
    public const TYPE_EXCESS = 'excess';

    protected $fillable = [
        'name',
        'type',
        'monthly_target',
        'priority_order',
        'cap',
        'sweeps_excess',
        'excess_percentage',
    ];

    protected $casts = [
        'monthly_target' => 'integer',
        'priority_order' => 'integer',
        'cap' => 'integer',
        'sweeps_excess' => 'boolean',
        'excess_percentage' => 'integer',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getBalanceAttribute(): int
    {
        if ($this->relationLoaded('transactions')) {
            return (int) $this->transactions->sum('amount');
        }

        return (int) $this->transactions()->sum('amount');
    }
}
