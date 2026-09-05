@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('buckets.index') }}" class="text-sm text-gold hover:text-gold-hover transition-colors">&larr; Back to Buckets</a>
    </div>

    {{-- Overdrawn buckets: offer a top-up from savings before closing the month --}}
    @if ($negativeBuckets->isNotEmpty())
        @php $savingsBalance = $primarySavings?->balance ?? 0; @endphp
        <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-6 mb-6 max-w-lg border border-crimson/30">
            <h2 class="font-serif text-xl font-bold text-warm-white mb-1">Buckets In The Red</h2>
            <p class="text-sm text-muted mb-4">
                @if ($primarySavings)
                    Top any of these up from <span class="text-warm-white font-semibold">{{ $primarySavings->name }}</span>
                    (${{ number_format($savingsBalance / 100, 2) }}) before closing the month. Each one is recorded as a transfer.
                @else
                    No primary savings bucket is designated, so there is nowhere to take the money from. Mark one bucket as primary savings first.
                @endif
            </p>

            <div class="space-y-2">
                @foreach ($negativeBuckets as $bucket)
                    @php $shortfall = -(int) $bucket->transactions_sum_amount; @endphp
                    <div class="flex items-center justify-between gap-4 rounded-lg bg-surface px-4 py-3">
                        <div class="min-w-0">
                            <a href="{{ route('buckets.show', $bucket) }}" class="text-warm-white font-semibold hover:text-gold transition-colors truncate block">
                                {{ $bucket->name }}
                            </a>
                            <p class="text-xs text-muted">Short by ${{ number_format($shortfall / 100, 2) }}</p>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="text-lg font-bold text-crimson">−${{ number_format($shortfall / 100, 2) }}</span>
                            @if ($primarySavings)
                                <form method="POST" action="{{ route('buckets.top-up', $bucket) }}">
                                    @csrf
                                    <button type="submit"
                                        class="rounded-lg bg-forest px-3 py-1.5 text-xs font-bold text-white hover:bg-forest/80 transition-colors whitespace-nowrap">
                                        Make Whole
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($primarySavings && $savingsBalance < $negativeBuckets->sum(fn ($b) => -(int) $b->transactions_sum_amount))
                <p class="text-xs text-crimson mt-4">
                    Heads up: {{ $primarySavings->name }} does not hold enough to cover every shortfall. Topping them all up would push it negative.
                </p>
            @endif
        </div>
    @endif

    <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-6 max-w-lg">
        <h1 class="font-serif text-3xl font-bold text-warm-white mb-2">End-of-Month Sweep</h1>
        <p class="text-sm text-muted mb-6">
            Close out <span class="text-gold font-semibold">{{ $activePeriod->format('F Y') }}</span> and transfer remaining balances from all buckets marked "Sweeps Excess" into your primary savings bucket.
        </p>

        <form method="POST" action="{{ route('sweep.store') }}"
            x-data="{ confirming: false }">
            @csrf
            <input type="hidden" name="month" value="{{ $activePeriod->format('Y-m') }}">

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
