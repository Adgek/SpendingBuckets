@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('buckets.index') }}" class="text-sm text-gold hover:text-gold-hover transition-colors">&larr; Back to Buckets</a>
    </div>

    @php $balance = (int) $bucket->transactions_sum_amount; @endphp

    <div class="rounded-lg bg-elevated border border-border p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="font-serif text-2xl font-bold text-warm-white">{{ $bucket->name }}</h1>
                <div class="flex items-center gap-3 mt-1">
                    <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $bucket->type === 'fixed' ? 'bg-gold/20 text-gold' : 'bg-surface text-muted' }}">
                        {{ ucfirst($bucket->type) }}
                    </span>
                    @if ($bucket->is_primary_savings)
                        <span class="text-xs bg-forest/20 text-forest-light px-2 py-0.5 rounded-full">Primary Savings</span>
                    @endif
                    @if ($bucket->sweeps_excess)
                        <span class="text-xs bg-surface text-muted px-2 py-0.5 rounded-full">Sweeps</span>
                    @endif
                </div>
            </div>
            <p class="text-3xl font-bold {{ $balance >= 0 ? 'text-warm-white' : 'text-crimson' }}">
                ${{ number_format($balance / 100, 2) }}
            </p>
        </div>

        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            @if ($bucket->monthly_target)
                <div class="bg-surface rounded-lg p-3">
                    <dt class="text-muted text-xs">Monthly Target</dt>
                    <dd class="font-semibold text-warm-white mt-0.5">${{ number_format($bucket->monthly_target / 100, 2) }}</dd>
                </div>
            @endif
            @if ($bucket->cap)
                <div class="bg-surface rounded-lg p-3">
                    <dt class="text-muted text-xs">Cap</dt>
                    <dd class="font-semibold text-warm-white mt-0.5">${{ number_format($bucket->cap / 100, 2) }}</dd>
                </div>
            @endif
            @if ($bucket->priority_order !== null)
                <div class="bg-surface rounded-lg p-3">
                    <dt class="text-muted text-xs">Priority</dt>
                    <dd class="font-semibold text-warm-white mt-0.5">#{{ $bucket->priority_order }}</dd>
                </div>
            @endif
            @if ($bucket->excess_percentage !== null)
                <div class="bg-surface rounded-lg p-3">
                    <dt class="text-muted text-xs">Excess %</dt>
                    <dd class="font-semibold text-warm-white mt-0.5">{{ $bucket->excess_percentage }}%</dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- Transaction History --}}
    <div class="rounded-lg bg-elevated border border-border p-6" x-data="{ filter: 'all' }">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-serif text-lg font-semibold text-warm-white">Transaction History</h2>
            <select x-model="filter" class="rounded-lg bg-surface border border-border text-warm-white px-3 py-1.5 text-xs focus:ring-2 focus:ring-gold focus:border-gold">
                <option value="all">All Types</option>
                <option value="allocation">Allocation</option>
                <option value="expense">Expense</option>
                <option value="transfer">Transfer</option>
                <option value="sweep">Sweep</option>
            </select>
        </div>

        <div class="divide-y divide-border">
            @php $runningBalance = 0; @endphp
            @forelse ($bucket->transactions->sortBy('created_at') as $txn)
                @php $runningBalance += $txn->amount; @endphp
                <div class="py-3 flex items-center justify-between"
                     x-show="filter === 'all' || filter === '{{ $txn->type }}'">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold flex-shrink-0
                            {{ match($txn->type) {
                                'allocation' => 'bg-forest/20 text-forest-light',
                                'expense' => 'bg-crimson/20 text-crimson',
                                'transfer' => 'bg-gold/20 text-gold',
                                'sweep' => 'bg-blue-500/20 text-blue-400',
                                default => 'bg-surface text-muted',
                            } }}">
                            {{ ucfirst($txn->type) }}
                        </span>
                        <span class="text-sm text-muted truncate">{{ $txn->description ?? '—' }}</span>
                        <span class="text-xs text-muted/60 flex-shrink-0">{{ $txn->created_at->format('M j, g:ia') }}</span>
                    </div>
                    <div class="flex items-center gap-4 flex-shrink-0">
                        <span class="font-mono text-sm {{ $txn->amount >= 0 ? 'text-forest-light' : 'text-crimson' }}">
                            {{ $txn->amount >= 0 ? '+' : '' }}${{ number_format($txn->amount / 100, 2) }}
                        </span>
                        <span class="font-mono text-xs text-muted w-20 text-right">
                            ${{ number_format($runningBalance / 100, 2) }}
                        </span>
                    </div>
                </div>
            @empty
                <p class="py-4 text-sm text-muted text-center">No transactions yet.</p>
            @endforelse
        </div>
    </div>
@endsection
