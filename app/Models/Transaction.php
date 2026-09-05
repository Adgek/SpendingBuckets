<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const TYPE_ALLOCATION = 'allocation';
    public const TYPE_EXPENSE = 'expense';
    public const TYPE_SWEEP = 'sweep';
    public const TYPE_TRANSFER = 'transfer';

    public const BALANCE_TOTAL = 'total';
    public const BALANCE_MONTHLY = 'monthly';

    protected $fillable = [
        'bucket_id',
        'deposit_id',
        'amount',
        'type',
        'balance_type',
        'reference_id',
        'description',
    ];

    protected $casts = [
        'amount' => 'integer',
        'created_at' => 'datetime',
    ];

    public function bucket(): BelongsTo
    {
        return $this->belongsTo(Bucket::class);
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }
}
