<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\ActivePeriodService;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge([
                'amount' => (int) round((float) $this->input('amount') * 100),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bucket_id' => ['required', 'integer', 'exists:buckets,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
            'expense_date' => ['required', 'date', 'before_or_equal:today', $this->withinActivePeriod()],
        ];
    }

    /**
     * An expense cannot be dated past the month currently being worked in. The active period
     * is the earliest month that has not been swept, so this blocks (for example) an August
     * expense while July is still open and would otherwise get swept along with it.
     */
    private function withinActivePeriod(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $activePeriod = app(ActivePeriodService::class)->current();
            $date = Carbon::parse($value);

            if ($date->lessThanOrEqualTo($activePeriod->copy()->endOfMonth())) {
                return;
            }

            $fail(sprintf(
                '%s has not been swept yet. Close it with an end-of-month sweep before recording expenses in %s.',
                $activePeriod->format('F Y'),
                $date->format('F Y'),
            ));
        };
    }
}
