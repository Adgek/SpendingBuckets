@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-3xl font-bold text-warm-white">Deposit History</h1>
        <a href="{{ route('deposits.create') }}" class="rounded-lg bg-gold px-4 py-2 text-sm font-semibold text-charcoal hover:bg-gold-hover transition-colors">New Deposit</a>
    </div>

    <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 overflow-hidden">
        @forelse ($deposits as $deposit)
            <div class="border-b border-border last:border-b-0" x-data="{ open: false }">
                <button @click="open = !open" class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-surface/50 transition-colors">
                    <div class="flex items-center gap-4">
                        <svg class="w-4 h-4 text-muted transition-transform" :class="open && 'rotate-90'" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-sm text-warm-white font-semibold">{{ $deposit->deposit_date->format('M j, Y') }}</p>
                            <p class="text-xs text-muted">{{ $deposit->description ?? 'No description' }}</p>
                        </div>
                    </div>
                    <span class="text-lg font-bold text-forest-light">${{ number_format($deposit->amount / 100, 2) }}</span>
                </button>

                <div x-show="open" x-collapse x-cloak class="px-5 pb-4">
                    <div class="bg-surface rounded-lg p-3 space-y-2">
                        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-2">Allocation Breakdown</p>
                        @forelse ($deposit->transactions as $txn)
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold
                                        {{ match($txn->type) {
                                            'allocation' => 'bg-forest/20 text-forest-light',
                                            'expense' => 'bg-crimson/20 text-crimson',
                                            'transfer' => 'bg-gold/20 text-gold',
                                            'sweep' => 'bg-blue-500/20 text-blue-400',
                                            default => 'bg-surface text-muted',
                                        } }}">
                                        {{ ucfirst($txn->type) }}
                                    </span>
                                    <span class="text-warm-white">{{ $txn->bucket->name ?? '—' }}</span>
                                </div>
                                <span class="font-mono text-forest-light">${{ number_format($txn->amount / 100, 2) }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-muted">No transactions recorded for this deposit.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @empty
            <div class="px-5 py-8 text-center">
                <p class="text-muted">No deposits yet.</p>
            </div>
        @endforelse
    </div>
@endsection
