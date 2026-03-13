@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('buckets.index') }}" class="text-sm text-gold hover:text-gold-hover transition-colors">&larr; Back to Buckets</a>
    </div>

    <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-6 max-w-lg" x-data="{ type: '{{ old('type', 'fixed') }}' }">
        <h1 class="font-serif text-3xl font-bold text-warm-white mb-6">Create Bucket</h1>

        <form method="POST" action="{{ route('buckets.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-muted mb-1">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                    placeholder="e.g. Mortgage">
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
                <input type="number" name="monthly_target" id="monthly_target" value="{{ old('monthly_target') }}" step="0.01" min="0"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                    placeholder="e.g. 1200.00">
                @error('monthly_target') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div x-show="type === 'fixed'" x-cloak>
                <label for="priority_order" class="block text-sm font-medium text-muted mb-1">Priority Order</label>
                <input type="number" name="priority_order" id="priority_order" value="{{ old('priority_order') }}"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                    placeholder="1">
                @error('priority_order') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div x-show="type === 'excess'" x-cloak>
                <label for="excess_percentage" class="block text-sm font-medium text-muted mb-1">Excess Percentage</label>
                <input type="number" name="excess_percentage" id="excess_percentage" value="{{ old('excess_percentage') }}" min="0" max="100"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                    placeholder="e.g. 30">
                @error('excess_percentage') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="cap" class="block text-sm font-medium text-muted mb-1">Cap ($)</label>
                <input type="number" name="cap" id="cap" value="{{ old('cap') }}" step="0.01" min="0"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                    placeholder="Leave blank for no cap">
                @error('cap') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-6 flex-wrap">
                <label class="flex items-center gap-2 text-sm text-muted cursor-pointer">
                    <input type="hidden" name="sweeps_excess" value="0">
                    <input type="checkbox" name="sweeps_excess" value="1" {{ old('sweeps_excess') ? 'checked' : '' }}
                        class="rounded border-border bg-surface text-gold focus:ring-gold w-5 h-5">
                    <span>Sweeps Excess</span>
                </label>
                <label class="flex items-center gap-2 text-sm text-muted cursor-pointer">
                    <input type="hidden" name="receives_sweeps" value="0">
                    <input type="checkbox" name="receives_sweeps" value="1" {{ old('receives_sweeps') ? 'checked' : '' }}
                        class="rounded border-border bg-surface text-gold focus:ring-gold w-5 h-5">
                    <span>Receives Sweeps</span>
                </label>
                <label class="flex items-center gap-2 text-sm text-muted cursor-pointer">
                    <input type="hidden" name="is_primary_savings" value="0">
                    <input type="checkbox" name="is_primary_savings" value="1" {{ old('is_primary_savings') ? 'checked' : '' }}
                        class="rounded border-border bg-surface text-gold focus:ring-gold w-5 h-5">
                    <span>Primary Savings</span>
                </label>
            </div>

            <button type="submit" class="rounded-lg bg-gold px-6 py-3 text-sm font-bold text-charcoal hover:bg-gold-hover transition-colors">
                Create Bucket
            </button>
        </form>
    </div>
@endsection
