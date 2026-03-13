@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('buckets.index') }}" class="text-sm text-gold hover:text-gold-hover transition-colors">&larr; Back to Buckets</a>
    </div>

    <div class="rounded-lg bg-elevated border border-border p-6 max-w-lg">
        <h1 class="font-serif text-2xl font-bold text-warm-white mb-6">Record Deposit</h1>

        <form method="POST" action="{{ route('deposits.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="amount" class="block text-sm font-medium text-muted mb-1">Amount ($)</label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" required min="0.01" step="0.01"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-4 py-3 text-2xl font-bold focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                    placeholder="0.00">
                @error('amount') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="deposit_date" class="block text-sm font-medium text-muted mb-1">Deposit Date</label>
                <input type="date" name="deposit_date" id="deposit_date" value="{{ old('deposit_date', date('Y-m-d')) }}" required
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold">
                @error('deposit_date') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-muted mb-1">Description (optional)</label>
                <input type="text" name="description" id="description" value="{{ old('description') }}"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                    placeholder="e.g. Paycheck">
                @error('description') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded-lg bg-gold px-6 py-3 text-sm font-bold text-charcoal hover:bg-gold-hover transition-colors">
                Process Deposit
            </button>
        </form>
    </div>
@endsection
