@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('buckets.index') }}" class="text-sm text-gold hover:text-gold-hover transition-colors">&larr; Back to Buckets</a>
    </div>

    <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-6 max-w-lg">
        <h1 class="font-serif text-3xl font-bold text-warm-white mb-2">End-of-Month Sweep</h1>
        <p class="text-sm text-muted mb-6">
            This will transfer remaining balances from all buckets marked "Sweeps Excess" into your primary savings bucket.
        </p>

        <form method="POST" action="{{ route('sweep.store') }}"
            x-data="{ confirming: false }">
            @csrf
            <input type="hidden" name="month" value="{{ now()->format('Y-m') }}">

            <div x-show="!confirming">
                <button type="button" @click="confirming = true"
                    class="rounded-lg bg-gold px-6 py-3 text-sm font-bold text-charcoal hover:bg-gold-hover transition-colors">
                    Run Sweep
                </button>
            </div>

            <div x-show="confirming" x-cloak class="space-y-4">
                <div class="rounded-lg bg-gold/10 border border-gold/30 px-4 py-3">
                    <p class="text-sm text-gold font-semibold">
                        Are you sure? This will sweep all eligible buckets to savings.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="rounded-lg bg-gold px-6 py-3 text-sm font-bold text-charcoal hover:bg-gold-hover transition-colors">
                        Yes, Run Sweep
                    </button>
                    <button type="button" @click="confirming = false"
                        class="rounded-lg bg-surface border border-border px-6 py-3 text-sm font-semibold text-muted hover:text-warm-white transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
