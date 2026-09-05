<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\TopUpBucketAction;
use App\Models\Bucket;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class TopUpController extends Controller
{
    public function store(Bucket $bucket, TopUpBucketAction $action): RedirectResponse
    {
        try {
            $amount = $action->execute($bucket);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with(
            'success',
            "{$bucket->name} topped up with \$" . number_format($amount / 100, 2) . ' from savings.'
        );
    }
}
