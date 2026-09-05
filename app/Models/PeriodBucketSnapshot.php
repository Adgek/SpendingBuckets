<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodBucketSnapshot extends Model
{
    protected $fillable = [
        'period_id',
        'bucket_id',
        'monthly_target',
        'funded',
        'paid',
        'swept',
        'closing_balance',
    ];

    protected $casts = [
        'monthly_target' => 'integer',
        'funded' => 'integer',
        'paid' => 'integer',
        'swept' => 'integer',
        'closing_balance' => 'integer',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function bucket(): BelongsTo
    {
        return $this->belongsTo(Bucket::class);
    }
}
