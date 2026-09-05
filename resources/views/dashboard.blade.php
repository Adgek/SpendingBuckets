@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="font-serif text-3xl font-bold text-warm-white mb-6">Dashboard</h1>

    @if ($isHistorical)
        <div class="mb-4">
            <a href="{{ route('dashboard') }}" class="text-sm text-gold hover:text-gold-hover transition-colors">&larr; Back to Current Period</a>
        </div>
    @endif

    {{-- Month Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mb-8">
        {{-- Current Month --}}
        <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-8">
            <h2 class="text-muted text-sm font-semibold uppercase tracking-wider mb-4">{{ $currentMonthLabel }}</h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-muted text-sm">Total Monthly Target</span>
                    <span class="text-warm-white text-xl font-bold">${{ number_format($totalMonthlyTarget / 100, 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-muted text-sm">Funded So Far</span>
                    <span class="text-forest-light text-xl font-bold">${{ number_format($totalFundedThisMonth / 100, 2) }}</span>
                </div>
                @php
                    $remaining = $totalMonthlyTarget - $totalFundedThisMonth;
                    $pct = $totalMonthlyTarget > 0 ? min(100, round($totalFundedThisMonth / $totalMonthlyTarget * 100)) : 0;
                @endphp
                <div class="flex items-center justify-between">
                    <span class="text-muted text-sm">Remaining</span>
                    <span class="text-gold text-xl font-bold">${{ number_format($remaining / 100, 2) }}</span>
                </div>
                @if ($otherIncome > 0)
                <div class="flex items-center justify-between border-t border-border pt-3">
                    <a href="{{ route('income-sources.index') }}" class="text-muted text-sm hover:text-gold transition-colors">Other Income</a>
                    <span class="text-forest-light text-xl font-bold">${{ number_format($otherIncome / 100, 2) }}</span>
                </div>
                @endif
                <div class="h-2 bg-surface rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700 ease-out {{ $pct >= 100 ? 'bg-forest shadow-[0_0_8px_rgba(45,106,79,0.4)]' : 'bg-gold shadow-[0_0_8px_rgba(197,160,89,0.3)]' }}" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        </div>

        {{-- Per Paycheck --}}
        <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-8 flex flex-col items-center justify-center text-center">
            <h2 class="text-muted text-sm font-semibold uppercase tracking-wider mb-2">Per Paycheck</h2>
            <p class="font-serif text-5xl font-bold text-gold tracking-tight">
                ${{ number_format($perPaycheck / 100, 2) }}
            </p>
            @if ($otherIncome > 0)
                <p class="text-muted text-xs mt-2">Based on 4 paychecks / month, after ${{ number_format($otherIncome / 100, 2) }} of other income</p>
                <p class="text-muted text-xs mt-1">Without other income: ${{ number_format($grossPerPaycheck / 100, 2) }}</p>
            @else
                <p class="text-muted text-xs mt-2">Based on 4 paychecks / month</p>
            @endif
        </div>
    </div>

    {{-- Last Month --}}
    <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-8 mb-8">
        <h2 class="text-muted text-sm font-semibold uppercase tracking-wider mb-4">{{ $lastMonthLabel }}</h2>
        <div class="flex items-center justify-between">
            <span class="text-muted text-sm">Total Funded</span>
            <span class="text-warm-white text-xl font-bold">${{ number_format($totalFundedLastMonth / 100, 2) }}</span>
        </div>
    </div>

    {{-- Bucket Breakdown --}}
    @if ($buckets->count())
    <div class="mt-4 mb-8 rounded-xl bg-elevated shadow-lg shadow-black/20 p-8 pt-8">
        <h2 class="text-muted text-sm font-semibold uppercase tracking-wider mb-4">Bucket Breakdown — {{ $currentMonthLabel }}</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted border-b border-border">
                        <th class="pb-3 font-semibold">#</th>
                        <th class="pb-3 font-semibold">Bucket</th>
                        <th class="pb-3 font-semibold text-right">Target</th>
                        <th class="pb-3 font-semibold text-right">Funded</th>
                        <th class="pb-3 font-semibold text-right">Per Paycheck</th>
                        <th class="pb-3 font-semibold text-right">%</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($buckets as $bucket)
                        @php
                            $target = $bucket->monthly_target ?? 0;
                            $funded = $bucket->funded_this_month;
                            $bucketPct = $target > 0 ? min(100, round($funded / $target * 100)) : 0;
                            $bucketPerPaycheck = (int) round($target / 4);
                        @endphp
                        <tr class="text-warm-white">
                            <td class="py-3 text-muted">{{ $bucket->priority_order }}</td>
                            <td class="py-3 font-semibold">
                                <a href="{{ route('buckets.show', $bucket) }}" class="hover:text-gold transition-colors">{{ $bucket->name }}</a>
                            </td>
                            <td class="py-3 text-right">${{ number_format($target / 100, 2) }}</td>
                            <td class="py-3 text-right {{ $funded >= $target ? 'text-forest-light' : '' }}">${{ number_format($funded / 100, 2) }}</td>
                            <td class="py-3 text-right text-gold">${{ number_format($bucketPerPaycheck / 100, 2) }}</td>
                            <td class="py-3 text-right {{ $bucketPct >= 100 ? 'text-forest-light' : 'text-muted' }}">{{ $bucketPct }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gold text-warm-white font-bold">
                        <td class="pt-3" colspan="2">Totals</td>
                        <td class="pt-3 text-right">${{ number_format($totalMonthlyTarget / 100, 2) }}</td>
                        <td class="pt-3 text-right text-forest-light">${{ number_format($totalFundedThisMonth / 100, 2) }}</td>
                        <td class="pt-3 text-right text-gold">${{ number_format($grossPerPaycheck / 100, 2) }}</td>
                        <td class="pt-3 text-right">{{ $totalMonthlyTarget > 0 ? round($totalFundedThisMonth / $totalMonthlyTarget * 100) : 0 }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    {{-- Recent Activity --}}
    <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-muted text-sm font-semibold uppercase tracking-wider">Recent Activity</h2>
            <a href="{{ route('deposits.index') }}" class="text-xs text-muted hover:text-gold transition-colors">View all &rarr;</a>
        </div>

        @forelse ($recentActivity as $entry)
            @php
                [$badgeClass, $badgeLabel, $amountClass, $amountPrefix] = match ($entry['kind']) {
                    'deposit' => ['bg-forest/20 text-forest-light', 'Deposit', 'text-forest-light', '+'],
                    'expense' => ['bg-crimson/20 text-crimson', 'Expense', 'text-crimson', '−'],
                    'transfer' => ['bg-gold/20 text-gold', 'Transfer', 'text-gold', ''],
                    default => ['bg-blue-500/20 text-blue-400', 'Sweep', 'text-blue-400', ''],
                };

                $line = match ($entry['kind']) {
                    'deposit' => $entry['description'] ?: 'Deposit',
                    'expense' => $entry['title'],
                    'transfer' => ($entry['from'] ?? 'Multiple') . ' → ' . ($entry['to'] ?? 'Multiple'),
                    default => $entry['sources']->count() . ' bucket' . ($entry['sources']->count() === 1 ? '' : 's') . ' drained',
                };
            @endphp
            <div class="flex items-center justify-between gap-4 py-2.5 border-b border-border last:border-b-0">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold flex-shrink-0 {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    <div class="min-w-0">
                        <p class="text-sm text-warm-white truncate">{{ $line }}</p>
                        <p class="text-xs text-muted">
                            {{ $entry['occurred_at']->format('M j, Y') }}
                            @if ($entry['kind'] === 'expense' && $entry['description']) &middot; {{ $entry['description'] }} @endif
                        </p>
                    </div>
                </div>
                <span class="text-sm font-bold flex-shrink-0 {{ $amountClass }}">{{ $amountPrefix }}${{ number_format($entry['amount'] / 100, 2) }}</span>
            </div>
        @empty
            <p class="text-muted text-sm">No activity yet.</p>
        @endforelse
    </div>
</div>
@endsection
