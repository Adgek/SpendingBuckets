@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="font-serif text-3xl font-bold text-warm-white mb-6">Dashboard</h1>

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
            <p class="text-muted text-xs mt-2">Based on 4 paychecks / month</p>
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
                        <td class="pt-3 text-right text-gold">${{ number_format($perPaycheck / 100, 2) }}</td>
                        <td class="pt-3 text-right">{{ $totalMonthlyTarget > 0 ? round($totalFundedThisMonth / $totalMonthlyTarget * 100) : 0 }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
