@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-3xl font-bold text-warm-white">Activity History</h1>
        <a href="{{ route('deposits.create') }}" class="rounded-lg bg-gold px-4 py-2 text-sm font-semibold text-charcoal hover:bg-gold-hover transition-colors">New Deposit</a>
    </div>

    {{-- Type Selector --}}
    @php
        $filters = [
            'all' => 'All',
            'deposits' => 'Deposits',
            'expenses' => 'Expenses',
            'transfers' => 'Transfers',
            'sweeps' => 'Sweeps',
        ];
    @endphp
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach ($filters as $value => $label)
            <a href="{{ route('deposits.index', $value === 'all' ? [] : ['type' => $value]) }}"
               class="rounded-lg px-3 py-1.5 text-sm font-semibold transition-colors {{ $activityType === $value ? 'bg-gold text-charcoal' : 'bg-elevated text-muted hover:text-warm-white' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 overflow-hidden">
        @forelse ($entries as $entry)
            @if ($entry['kind'] === 'deposit')
                @php $deposit = $entry['deposit']; @endphp
                <div class="border-b border-border last:border-b-0" x-data="{ open: false }">
                    <button @click="open = !open" class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-surface/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <svg class="w-4 h-4 text-muted transition-transform" :class="open && 'rotate-90'" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold bg-forest/20 text-forest-light">Deposit</span>
                            <div>
                                <p class="text-sm text-warm-white font-semibold">{{ $entry['occurred_at']->format('M j, Y') }}</p>
                                <p class="text-xs text-muted">{{ $entry['description'] ?? 'No description' }}</p>
                            </div>
                        </div>
                        <span class="text-lg font-bold text-forest-light">+${{ number_format($entry['amount'] / 100, 2) }}</span>
                    </button>

                    <div x-show="open" x-collapse x-cloak class="px-5 pb-4">
                        <div class="bg-surface rounded-lg p-3 space-y-2">
                            <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-2">Allocation Breakdown</p>
                            @forelse ($entry['transactions'] as $txn)
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold bg-forest/20 text-forest-light">
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

                        <div class="mt-3 flex justify-end" x-data="{ confirm: false }">
                            <button x-show="!confirm" @click="confirm = true" class="text-xs text-crimson hover:text-crimson-hover transition-colors flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                                Undo Deposit
                            </button>
                            <form x-show="confirm" x-cloak method="POST" action="{{ route('deposits.destroy', $deposit) }}" class="flex items-center gap-2">
                                @csrf
                                @method('DELETE')
                                <span class="text-xs text-crimson">Are you sure?</span>
                                <button type="submit" class="rounded bg-crimson px-2.5 py-1 text-xs font-semibold text-white hover:bg-crimson-hover transition-colors">Yes, Undo</button>
                                <button type="button" @click="confirm = false" class="rounded bg-surface px-2.5 py-1 text-xs font-semibold text-muted hover:text-warm-white transition-colors">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>

            @elseif ($entry['kind'] === 'expense')
                @php $txn = $entry['transaction']; @endphp
                <div class="border-b border-border last:border-b-0 px-5 py-4 flex items-center justify-between gap-4" x-data="{ confirm: false }">
                    <div class="flex items-center gap-4 min-w-0">
                        <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold bg-crimson/20 text-crimson flex-shrink-0">Expense</span>
                        <div class="min-w-0">
                            <p class="text-sm text-warm-white font-semibold truncate">{{ $entry['title'] }}</p>
                            <p class="text-xs text-muted truncate">
                                {{ $entry['occurred_at']->format('M j, Y') }}
                                @if ($entry['description']) &middot; {{ $entry['description'] }} @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="text-lg font-bold text-crimson">−${{ number_format($entry['amount'] / 100, 2) }}</span>
                        <button x-show="!confirm" @click="confirm = true" class="text-xs text-muted hover:text-crimson transition-colors" title="Undo expense">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                        </button>
                        <form x-show="confirm" x-cloak method="POST" action="{{ route('expenses.destroy', $txn) }}" class="flex items-center gap-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded bg-crimson px-2.5 py-1 text-xs font-semibold text-white hover:bg-crimson-hover transition-colors">Undo</button>
                            <button type="button" @click="confirm = false" class="rounded bg-surface px-2.5 py-1 text-xs font-semibold text-muted hover:text-warm-white transition-colors">Cancel</button>
                        </form>
                    </div>
                </div>

            @elseif ($entry['kind'] === 'transfer')
                <div class="border-b border-border last:border-b-0 px-5 py-4 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 min-w-0">
                        <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold bg-gold/20 text-gold flex-shrink-0">Transfer</span>
                        <div class="min-w-0">
                            <p class="text-sm text-warm-white font-semibold truncate">
                                {{ $entry['from'] ?? 'Multiple buckets' }}
                                <span class="text-muted">&rarr;</span>
                                {{ $entry['to'] ?? 'Multiple buckets' }}
                            </p>
                            <p class="text-xs text-muted truncate">
                                {{ $entry['occurred_at']->format('M j, Y') }}
                                @if ($entry['description']) &middot; {{ $entry['description'] }} @endif
                                @if ($entry['balance_type']) &middot; {{ ucfirst($entry['balance_type']) }} balance @endif
                            </p>
                        </div>
                    </div>
                    <span class="text-lg font-bold text-gold flex-shrink-0">${{ number_format($entry['amount'] / 100, 2) }}</span>
                </div>

            @else
                <div class="border-b border-border last:border-b-0 px-5 py-4" x-data="{ open: false }">
                    <button @click="open = !open" class="w-full flex items-center justify-between text-left gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            <svg class="w-4 h-4 text-muted transition-transform flex-shrink-0" :class="open && 'rotate-90'" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold bg-blue-500/20 text-blue-400 flex-shrink-0">Sweep</span>
                            <div class="min-w-0">
                                <p class="text-sm text-warm-white font-semibold truncate">
                                    {{ $entry['sources']->count() }} bucket{{ $entry['sources']->count() === 1 ? '' : 's' }} drained
                                </p>
                                <p class="text-xs text-muted">{{ $entry['occurred_at']->format('M j, Y') }}</p>
                            </div>
                        </div>
                        <span class="text-lg font-bold text-blue-400 flex-shrink-0">${{ number_format($entry['amount'] / 100, 2) }}</span>
                    </button>

                    <div x-show="open" x-collapse x-cloak class="mt-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm bg-surface rounded-lg p-3">
                            <div>
                                <h4 class="text-xs uppercase tracking-wider text-muted mb-2">Drained from</h4>
                                <ul class="space-y-1">
                                    @foreach ($entry['sources'] as $source)
                                        <li class="flex justify-between text-warm-white">
                                            <span>{{ $source['bucket'] }}</span>
                                            <span class="text-crimson">${{ number_format($source['amount'] / 100, 2) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div>
                                <h4 class="text-xs uppercase tracking-wider text-muted mb-2">Received into</h4>
                                <ul class="space-y-1">
                                    @foreach ($entry['destinations'] as $destination)
                                        <li class="flex justify-between text-warm-white">
                                            <span>{{ $destination['bucket'] }}</span>
                                            <span class="text-forest-light">${{ number_format($destination['amount'] / 100, 2) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="px-5 py-8 text-center">
                <p class="text-muted">No activity yet{{ $activityType === 'all' ? '' : ' of this kind' }}.</p>
            </div>
        @endforelse
    </div>
@endsection
