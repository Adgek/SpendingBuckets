@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('income-sources.index') }}" class="text-sm text-gold hover:text-gold-hover transition-colors">&larr; Back to Other Income</a>
    </div>

    <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-6 max-w-lg">
        <h1 class="font-serif text-3xl font-bold text-warm-white mb-6">Add Income Source</h1>

        <form method="POST" action="{{ route('income-sources.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-muted mb-1">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                    placeholder="e.g. Rental Property">
                @error('name') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-muted mb-1">Amount Per Month ($)</label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                    placeholder="e.g. 1800.00">
                @error('amount') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-muted mb-1">Description</label>
                <input type="text" name="description" id="description" value="{{ old('description') }}"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                    placeholder="e.g. Tenants at the old house">
                @error('description') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-gold px-5 py-2.5 text-sm font-semibold text-charcoal hover:bg-gold-hover transition-colors">
                    Add Income Source
                </button>
                <a href="{{ route('income-sources.index') }}" class="text-sm text-muted hover:text-warm-white transition-colors">Cancel</a>
            </div>
        </form>
    </div>
@endsection
