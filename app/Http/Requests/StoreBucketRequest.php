<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Bucket;
use Illuminate\Foundation\Http\FormRequest;

class StoreBucketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $conversions = [];

        if ($this->has('monthly_target') && $this->input('monthly_target') !== null) {
            $conversions['monthly_target'] = (int) round((float) $this->input('monthly_target') * 100);
        }

        if ($this->has('cap') && $this->input('cap') !== null) {
            $conversions['cap'] = (int) round((float) $this->input('cap') * 100);
        }

        if ($conversions) {
            $this->merge($conversions);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:' . Bucket::TYPE_FIXED . ',' . Bucket::TYPE_EXCESS],
            'monthly_target' => ['required_if:type,' . Bucket::TYPE_FIXED, 'nullable', 'integer', 'min:1'],
            'priority_order' => ['nullable', 'integer', 'min:0'],
            'cap' => ['nullable', 'integer', 'min:1'],
            'sweeps_excess' => ['nullable', 'boolean'],
            'excess_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_primary_savings' => ['nullable', 'boolean'],
        ];
    }
}
