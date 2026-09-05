@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('income-sources.index') }}" class="text-sm text-gold hover:text-gold-hover transition-colors">&larr; Back to Other Income</a>
    </div>

    <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-6 max-w-lg">
        <h1 class="font-serif text-3xl font-bold text-warm-white mb-6">Edit Income Source</h1>

        <form method="POST" action="{{ route('income-sources.update', $incomeSource) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-muted mb-1">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $incomeSource->name) }}" required
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold">
                @error('name') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-muted mb-1">Amount Per Month ($)</label>
                <input type="number" name="amount" id="amount" step="0.01" min="0.01" required
                    value="{{ old('amount', number_format($incomeSource->amount / 100, 2, '.', '')) }}"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold">
                @error('amount') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-muted mb-1">Description</label>
                <input type="text" name="description" id="description" value="{{ old('description', $incomeSource->description) }}"
                    class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold">
                @error('description') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-muted cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $incomeSource->is_active) ? 'checked' : '' }}
                    class="rounded border-border bg-surface text-gold focus:ring-gold w-5 h-5">
                <span>Currently receiving this income</span>
            </label>

            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-gold px-5 py-2.5 text-sm font-semibold text-charcoal hover:bg-gold-hover transition-colors">
                        Save Changes
                    </button>
                    <a href="{{ route('income-sources.index') }}" class="text-sm text-muted hover:text-warm-white transition-colors">Cancel</a>
                </div>
            </div>
        </form>

        <div class="mt-6 pt-6 border-t border-border" x-data="{ confirm: false }">
            <button x-show="!confirm" @click="confirm = true" type="button" class="text-sm text-crimson hover:text-crimson-hover transition-colors">
                Delete this income source
            </button>
            <form x-show="confirm" x-cloak method="POST" action="{{ route('income-sources.destroy', $incomeSource) }}" class="flex items-center gap-2">
                @csrf
                @method('DELETE')
                <span class="text-sm text-crimson">Are you sure?</span>
                <button type="submit" class="rounded bg-crimson px-3 py-1.5 text-xs font-semibold text-white hover:bg-crimson-hover transition-colors">Yes, Delete</button>
                <button type="button" @click="confirm = false" class="rounded bg-surface px-3 py-1.5 text-xs font-semibold text-muted hover:text-warm-white transition-colors">Cancel</button>
            </form>
        </div>
    </div>
@endsection
