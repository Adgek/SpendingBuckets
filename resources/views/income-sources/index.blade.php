@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-3xl font-bold text-warm-white">Other Income</h1>
        <a href="{{ route('income-sources.create') }}" class="rounded-lg bg-gold px-4 py-2 text-sm font-semibold text-charcoal hover:bg-gold-hover transition-colors">
            + New Income
        </a>
    </div>

    <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-5 mb-6 flex items-center gap-6">
        <div>
            <p class="text-muted text-xs uppercase tracking-wider">Total / Month</p>
            <p class="font-serif text-3xl font-bold text-forest-light mt-1">${{ number_format($monthlyTotal / 100, 2) }}</p>
        </div>
        <div class="h-10 w-px bg-border"></div>
        <div>
            <p class="text-muted text-xs uppercase tracking-wider">Equivalent / Paycheck</p>
            <p class="text-warm-white text-lg font-bold mt-1">${{ number_format($perPaycheck / 100, 2) }}</p>
        </div>
    </div>

    <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 overflow-hidden">
        @forelse ($incomeSources as $source)
            <div class="border-b border-border last:border-b-0 px-5 py-4 flex items-center gap-4">
                <div class="flex-1 min-w-0">
                    <p class="text-warm-white font-semibold flex items-center gap-2">
                        {{ $source->name }}
                        @unless ($source->is_active)
                            <span class="text-xs bg-surface text-muted px-1.5 py-0.5 rounded-full">Paused</span>
                        @endunless
                    </p>
                    @if ($source->description)
                        <p class="text-xs text-muted mt-0.5">{{ $source->description }}</p>
                    @endif
                </div>

                <div class="text-right flex-shrink-0">
                    <p class="text-lg font-bold {{ $source->is_active ? 'text-forest-light' : 'text-muted' }}">
                        ${{ number_format($source->amount / 100, 2) }}
                    </p>
                    <p class="text-[10px] text-muted uppercase tracking-tighter">Per Month</p>
                </div>

                <a href="{{ route('income-sources.edit', $source) }}" class="text-muted hover:text-gold transition-colors flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </a>
            </div>
        @empty
            <div class="px-5 py-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-gold/10 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-muted mb-4">No other income yet. Add rent from a property, a side gig, anything recurring.</p>
                <a href="{{ route('income-sources.create') }}" class="inline-block rounded-lg bg-gold px-5 py-2.5 text-sm font-semibold text-charcoal hover:bg-gold-hover transition-colors">
                    Add Your First Income Source
                </a>
            </div>
        @endforelse
    </div>

    <p class="text-xs text-muted mt-4">
        Other income lowers what each paycheck has to cover. It does not create deposits on its own — record the money as a deposit when it actually lands.
    </p>
@endsection
