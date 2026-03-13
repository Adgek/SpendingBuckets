@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('buckets.index') }}" class="text-sm text-gold hover:text-gold-hover transition-colors">&larr; Back to Buckets</a>
    </div>

    <div class="rounded-lg bg-elevated border border-border p-6 max-w-lg" x-data="{ type: '{{ old('type', $bucket->type) }}' }">
        <h1 class="font-serif text-2xl font-bold text-warm-white mb-6">Edit: {{ $bucket->name }}</h1>

        <form method="POST" action="{{ route('buckets.update', $bucket) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-muted mb-1">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $bucket->name) }}" required
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold">
                @error('name') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-muted mb-1">Type</label>
                <select name="type" id="type" required x-model="type"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold">
                    <option value="fixed">Fixed</option>
                    <option value="excess">Excess</option>
                </select>
                @error('type') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div x-show="type === 'fixed'" x-cloak>
                <label for="monthly_target" class="block text-sm font-medium text-muted mb-1">Monthly Target ($)</label>
                <input type="number" name="monthly_target" id="monthly_target"
                    value="{{ old('monthly_target', $bucket->monthly_target !== null ? number_format($bucket->monthly_target / 100, 2, '.', '') : '') }}"
                    step="0.01" min="0"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                    placeholder="e.g. 1200.00">
                @error('monthly_target') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div x-show="type === 'fixed'" x-cloak>
                <label for="priority_order" class="block text-sm font-medium text-muted mb-1">Priority Order</label>
                <input type="number" name="priority_order" id="priority_order"
                    value="{{ old('priority_order', $bucket->priority_order) }}"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold">
                @error('priority_order') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div x-show="type === 'excess'" x-cloak>
                <label for="excess_percentage" class="block text-sm font-medium text-muted mb-1">Excess Percentage</label>
                <input type="number" name="excess_percentage" id="excess_percentage"
                    value="{{ old('excess_percentage', $bucket->excess_percentage) }}" min="0" max="100"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold">
                @error('excess_percentage') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="cap" class="block text-sm font-medium text-muted mb-1">Cap ($)</label>
                <input type="number" name="cap" id="cap"
                    value="{{ old('cap', $bucket->cap !== null ? number_format($bucket->cap / 100, 2, '.', '') : '') }}"
                    step="0.01" min="0"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                    placeholder="Leave blank for no cap">
                @error('cap') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 text-sm text-muted cursor-pointer">
                    <input type="hidden" name="sweeps_excess" value="0">
                    <input type="checkbox" name="sweeps_excess" value="1" {{ old('sweeps_excess', $bucket->sweeps_excess) ? 'checked' : '' }}
                        class="rounded border-border bg-surface text-gold focus:ring-gold w-5 h-5">
                    <span>Sweeps Excess</span>
                </label>
                <label class="flex items-center gap-2 text-sm text-muted cursor-pointer">
                    <input type="hidden" name="is_primary_savings" value="0">
                    <input type="checkbox" name="is_primary_savings" value="1" {{ old('is_primary_savings', $bucket->is_primary_savings) ? 'checked' : '' }}
                        class="rounded border-border bg-surface text-gold focus:ring-gold w-5 h-5">
                    <span>Primary Savings</span>
                </label>
            </div>

            <button type="submit" class="rounded-lg bg-gold px-6 py-3 text-sm font-bold text-charcoal hover:bg-gold-hover transition-colors">
                Update Bucket
            </button>
        </form>

        @php $balance = (int) ($bucket->transactions_sum_amount ?? $bucket->balance); @endphp
        <div class="mt-6 border-t border-border pt-4">
            <form method="POST" action="{{ route('buckets.destroy', $bucket) }}">
                @csrf
                @method('DELETE')
                @if ($balance > 0)
                    <p class="text-sm text-muted mb-2">
                        This bucket has a balance of <span class="text-gold">${{ number_format($balance / 100, 2) }}</span>. Transfer or sweep the funds before deleting.
                    </p>
                    <button type="submit" disabled class="text-sm text-muted/50 cursor-not-allowed">
                        Delete this bucket
                    </button>
                @else
                    <button type="submit" class="text-sm text-crimson hover:text-crimson-hover transition-colors"
                        onclick="return confirm('Are you sure you want to delete this bucket?')">
                        Delete this bucket
                    </button>
                @endif
            </form>
        </div>
    </div>
@endsection
