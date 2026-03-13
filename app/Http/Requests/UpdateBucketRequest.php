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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', 'in:' . Bucket::TYPE_FIXED . ',' . Bucket::TYPE_EXCESS],
            'monthly_target' => ['required_if:type,' . Bucket::TYPE_FIXED, 'nullable', 'integer', 'min:1'],
            'priority_order' => ['nullable', 'integer', 'min:0'],
            'cap' => ['nullable', 'integer', 'min:1'],
            'sweeps_excess' => ['nullable', 'boolean'],
            'receives_sweeps' => ['nullable', 'boolean'],
            'excess_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_primary_savings' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->boolean('is_primary_savings')) {
                $existing = Bucket::where('is_primary_savings', true)
                    ->where('id', '!=', $this->route('bucket')->id)
                    ->exists();

                if ($existing) {
                    $validator->errors()->add('is_primary_savings', 'A primary savings bucket already exists. Only one is allowed.');
                }
            }
        });
    }
}
