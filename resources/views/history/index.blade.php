@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="font-serif text-3xl font-bold text-warm-white mb-6">History</h1>

    @if ($closedPeriods->count())
        <div class="space-y-6">
            @foreach ($closedPeriods as $period)
                <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-8">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-warm-white font-semibold text-lg">{{ $period->month->format('F Y') }}</h2>
                        <div class="flex items-center gap-4">
                            @if ($period->closing_balance !== null)
                                <span class="text-sm text-gold font-semibold">${{ number_format($period->closing_balance / 100, 2) }}</span>
                            @endif
                            <span class="text-xs text-muted">Closed {{ $period->closed_at->format('M j, Y') }}</span>
                            <a href="{{ route('dashboard', ['month' => $period->month->format('Y-m')]) }}" class="text-muted hover:text-gold transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>

                    @if ($period->bucketSnapshots->count())
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-muted border-b border-border">
                                        <th class="pb-2 font-semibold">Bucket</th>
                                        <th class="pb-2 font-semibold text-right">Target</th>
                                        <th class="pb-2 font-semibold text-right">Funded</th>
                                        <th class="pb-2 font-semibold text-right">Paid</th>
                                        <th class="pb-2 font-semibold text-right">Swept</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @foreach ($period->bucketSnapshots as $snapshot)
                                        <tr class="text-warm-white">
                                            <td class="py-2 font-semibold">{{ $snapshot->bucket->name ?? 'Deleted' }}</td>
                                            <td class="py-2 text-right">${{ number_format($snapshot->monthly_target / 100, 2) }}</td>
                                            <td class="py-2 text-right text-forest-light">${{ number_format($snapshot->funded / 100, 2) }}</td>
                                            <td class="py-2 text-right text-crimson">${{ number_format($snapshot->paid / 100, 2) }}</td>
                                            <td class="py-2 text-right text-gold">${{ number_format($snapshot->swept / 100, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-gold text-warm-white font-bold">
                                        <td class="pt-2">Totals</td>
                                        <td class="pt-2 text-right">${{ number_format($period->bucketSnapshots->sum('monthly_target') / 100, 2) }}</td>
                                        <td class="pt-2 text-right text-forest-light">${{ number_format($period->bucketSnapshots->sum('funded') / 100, 2) }}</td>
                                        <td class="pt-2 text-right text-crimson">${{ number_format($period->bucketSnapshots->sum('paid') / 100, 2) }}</td>
                                        <td class="pt-2 text-right text-gold">${{ number_format($period->bucketSnapshots->sum('swept') / 100, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif

                    @php $sweepEvents = $period->sweepEvents(); @endphp
                    @if ($sweepEvents->count())
                        <div x-data="{ open: false }" class="mt-6 border-t border-border pt-4">
                            <button type="button" @click="open = !open"
                                class="flex items-center justify-between w-full text-left text-warm-white font-semibold hover:text-gold transition-colors">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/></svg>
                                    Sweep Audit
                                </span>
                                <span class="text-xs text-muted">{{ $sweepEvents->count() }} event{{ $sweepEvents->count() === 1 ? '' : 's' }}</span>
                            </button>
                            <div x-show="open" x-cloak class="mt-4 space-y-4">
                                @foreach ($sweepEvents as $event)
                                    <div class="rounded-lg bg-surface border border-border p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-xs text-muted">{{ $event['occurred_at']->format('M j, Y g:ia') }}</span>
                                            <span class="text-sm text-gold font-semibold">Total swept: ${{ number_format($event['total'] / 100, 2) }}</span>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <h4 class="text-xs uppercase tracking-wider text-muted mb-2">Sources (drained from)</h4>
                                                @if ($event['sources']->count())
                                                    <ul class="space-y-1">
                                                        @foreach ($event['sources'] as $source)
                                                            <li class="flex justify-between text-warm-white">
                                                                <span>{{ $source['bucket'] }}</span>
                                                                <span class="text-crimson">${{ number_format($source['amount'] / 100, 2) }}</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <p class="text-muted italic">No sources</p>
                                                @endif
                                            </div>
                                            <div>
                                                <h4 class="text-xs uppercase tracking-wider text-muted mb-2">Destinations (received into)</h4>
                                                @if ($event['destinations']->count())
                                                    <ul class="space-y-1">
                                                        @foreach ($event['destinations'] as $destination)
                                                            <li class="flex justify-between text-warm-white">
                                                                <span>{{ $destination['bucket'] }}</span>
                                                                <span class="text-forest-light">${{ number_format($destination['amount'] / 100, 2) }}</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <p class="text-muted italic">No destinations</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-8 text-center">
            <p class="text-muted">No months have been closed yet. Run your first sweep to close a month.</p>
        </div>
    @endif
</div>
@endsection
