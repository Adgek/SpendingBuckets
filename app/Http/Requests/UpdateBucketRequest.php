<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Bucket;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBucketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', 'in:' . Bucket::TYPE_FIXED . ',' . Bucket::TYPE_EXCESS],
            'monthly_target' => ['required_if:type,' . Bucket::TYPE_FIXED, 'nullable', 'integer', 'min:1'],
            'priority_order' => ['nullable', 'integer', 'min:0'],
            'cap' => ['nullable', 'integer', 'min:1'],
            'sweeps_excess' => ['nullable', 'boolean'],
            'excess_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_primary_savings' => ['nullable', 'boolean'],
        ];
    }
}
