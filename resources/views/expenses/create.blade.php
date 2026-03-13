@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('buckets.index') }}" class="text-sm text-gold hover:text-gold-hover transition-colors">&larr; Back to Buckets</a>
    </div>

    <div class="rounded-lg bg-elevated border border-border p-6 max-w-lg">
        <h1 class="font-serif text-2xl font-bold text-warm-white mb-6">Record Expense</h1>

        <form method="POST" action="{{ route('expenses.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="bucket_id" class="block text-sm font-medium text-muted mb-1">Bucket</label>
                <select name="bucket_id" id="bucket_id" required
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold">
                    <option value="">Select a bucket...</option>
                    @foreach ($buckets as $bucket)
                        <option value="{{ $bucket->id }}" {{ old('bucket_id') == $bucket->id ? 'selected' : '' }}>
                            {{ $bucket->name }}
                        </option>
                    @endforeach
                </select>
                @error('bucket_id') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-muted mb-1">Amount ($)</label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" required min="0.01" step="0.01"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                    placeholder="e.g. 45.00">
                @error('amount') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-muted mb-1">Description (optional)</label>
                <input type="text" name="description" id="description" value="{{ old('description') }}"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                    placeholder="e.g. Monthly water bill">
                @error('description') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded-lg bg-crimson px-6 py-3 text-sm font-bold text-white hover:bg-crimson-hover transition-colors">
                Record Expense
            </button>
        </form>
    </div>
@endsection
