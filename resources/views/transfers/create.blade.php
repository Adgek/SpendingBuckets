@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('buckets.index') }}" class="text-sm text-gold hover:text-gold-hover transition-colors">&larr; Back to Buckets</a>
    </div>

    <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-6 max-w-lg">
        <h1 class="font-serif text-3xl font-bold text-warm-white mb-6">Transfer Between Buckets</h1>

        <div class="rounded-lg bg-crimson/10 border border-crimson/30 px-3 py-2 mb-4">
            <p class="text-xs text-crimson font-semibold flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                Restricted Action — Danger Mode
            </p>
        </div>

        <form method="POST" action="{{ route('transfers.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="source_bucket_id" class="block text-sm font-medium text-muted mb-1">From Bucket</label>
                <select name="source_bucket_id" id="source_bucket_id" required
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-crimson focus:border-crimson">
                    <option value="">Select source...</option>
                    @foreach ($buckets as $bucket)
                        <option value="{{ $bucket->id }}" {{ old('source_bucket_id') == $bucket->id ? 'selected' : '' }}>
                            {{ $bucket->name }}
                        </option>
                    @endforeach
                </select>
                @error('source_bucket_id') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="destination_bucket_id" class="block text-sm font-medium text-muted mb-1">To Bucket</label>
                <select name="destination_bucket_id" id="destination_bucket_id" required
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-crimson focus:border-crimson">
                    <option value="">Select destination...</option>
                    @foreach ($buckets as $bucket)
                        <option value="{{ $bucket->id }}" {{ old('destination_bucket_id') == $bucket->id ? 'selected' : '' }}>
                            {{ $bucket->name }}
                        </option>
                    @endforeach
                </select>
                @error('destination_bucket_id') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-muted mb-1">Amount ($)</label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" required min="0.01" step="0.01"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-crimson focus:border-crimson placeholder-muted/50"
                    placeholder="e.g. 200.00">
                @error('amount') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-muted mb-1">Description (optional)</label>
                <input type="text" name="description" id="description" value="{{ old('description') }}"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-crimson focus:border-crimson placeholder-muted/50"
                    placeholder="e.g. Emergency car repair">
                @error('description') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded-lg bg-crimson px-6 py-3 text-sm font-bold text-white hover:bg-crimson-hover transition-colors">
                Execute Transfer
            </button>
        </form>
    </div>
@endsection
