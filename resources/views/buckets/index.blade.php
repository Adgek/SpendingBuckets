@extends('layouts.app')

@section('content')
<div class="flex flex-col lg:flex-row gap-8">
    {{-- Left: Bucket Stack --}}
    <div class="flex-1 min-w-0">
        {{-- Summary Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="font-serif text-2xl font-bold text-warm-white">Your Buckets</h1>
                <p class="text-muted text-sm mt-1">Total Balance: <span class="text-gold font-semibold">${{ number_format(($totalBalance ?? 0) / 100, 2) }}</span></p>
            </div>
            <a href="{{ route('buckets.create') }}" class="rounded-lg bg-gold px-4 py-2 text-sm font-semibold text-charcoal hover:bg-gold-hover transition-colors">
                + New Bucket
            </a>
        </div>

        {{-- Monthly Target / Per Paycheck --}}
        <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-4 mb-6 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <div>
                    <p class="text-muted text-xs uppercase tracking-wider">Total / Month</p>
                    <p class="text-warm-white text-lg font-bold">${{ number_format($totalMonthlyTarget / 100, 2) }}</p>
                </div>
                <div class="h-8 w-px bg-border"></div>
                <div>
                    <p class="text-muted text-xs uppercase tracking-wider">Each Paycheck (÷4)</p>
                    <p class="text-gold text-lg font-bold">${{ number_format($perPaycheck / 100, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Fixed Buckets (Priority Stack) --}}
        @if ($fixedBuckets->count())
        <div class="mb-8">
            <h2 class="font-serif text-lg font-semibold text-warm-white mb-4 mt-2 flex items-center gap-2">
                <svg class="w-5 h-5 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/></svg>
                Fixed Priority Stack
            </h2>
            <div id="fixed-bucket-list" class="grid gap-3" x-data="{ reorderError: false }" x-init="
                Sortable.create($el, {
                    handle: '.drag-handle',
                    animation: 200,
                    ghostClass: 'opacity-30',
                    draggable: '[data-bucket-id]',
                    onChoose(evt) { evt.item.classList.add('ring-2', 'ring-gold'); },
                    onUnchoose(evt) { evt.item.classList.remove('ring-2', 'ring-gold'); },
                    onEnd(evt) {
                        const container = evt.to;
                        const ids = Array.from(container.children).filter(el => el.dataset.bucketId).map(el => parseInt(el.dataset.bucketId));
                        const oldIndex = evt.oldIndex;
                        const newIndex = evt.newIndex;
                        fetch('{{ route('buckets.reorder') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ order: ids })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Reorder failed');
                            }
                            $data.reorderError = false;
                            container.querySelectorAll('[data-bucket-id] .priority-badge').forEach((badge, i) => {
                                if (!badge.querySelector('svg')) badge.textContent = i + 1;
                            });
                        })
                        .catch(() => {
                            // Revert the DOM to previous order
                            const movedEl = container.children[newIndex];
                            if (oldIndex < newIndex) {
                                container.insertBefore(movedEl, container.children[oldIndex]);
                            } else {
                                container.insertBefore(movedEl, container.children[oldIndex + 1]);
                            }
                            $data.reorderError = true;
                            setTimeout(() => $data.reorderError = false, 4000);
                        });
                    }
                })
            ">
                <div x-show="reorderError" x-cloak class="rounded-lg bg-crimson/20 border border-crimson px-3 py-2 text-sm text-crimson">
                    Reorder failed — order has been reverted. Please try again.
                </div>
                @foreach ($fixedBuckets as $bucket)
                    @php
                        $balance = (int) $bucket->transactions_sum_amount;
                        $fundedThisMonth = (int) $bucket->funded_this_month;
                        $target = $bucket->monthly_target ?? 0;
                        $pct = $target > 0 ? min(100, round($fundedThisMonth / $target * 100)) : 0;
                        $isFunded = $target > 0 && $fundedThisMonth >= $target;
                    @endphp
                    <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-4 flex items-center gap-4 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200"
                         data-bucket-id="{{ $bucket->id }}">
                        {{-- Drag Handle --}}
                        <div class="drag-handle cursor-grab active:cursor-grabbing text-muted hover:text-gold transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/></svg>
                        </div>

                        {{-- Priority Badge --}}
                        <div class="priority-badge flex-shrink-0 w-8 h-8 rounded-full {{ $isFunded ? 'bg-forest' : 'bg-surface' }} flex items-center justify-center text-sm font-bold {{ $isFunded ? 'text-white' : 'text-muted' }}">
                            @if ($isFunded)
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            @else
                                {{ $bucket->priority_order }}
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('buckets.show', $bucket) }}" class="text-warm-white font-semibold hover:text-gold transition-colors truncate block">
                                {{ $bucket->name }}
                            </a>
                            <div class="flex items-center gap-2 text-xs text-muted mt-0.5">
                                <span class="text-warm-white font-medium">Balance: ${{ number_format($balance / 100, 2) }}</span>
                                <span>•</span>
                                <span>Target: ${{ number_format($target / 100, 2) }}</span>
                            </div>

                            {{-- Progress Bar (Funded Status) --}}
                            <div class="mt-2 h-2 bg-surface rounded-full overflow-hidden relative" title="Funded this month: ${{ number_format($fundedThisMonth / 100, 2) }}">
                                <div class="absolute inset-y-0 left-1/4 w-px bg-border"></div>
                                <div class="absolute inset-y-0 left-1/2 w-px bg-border"></div>
                                <div class="absolute inset-y-0 left-3/4 w-px bg-border"></div>
                                <div class="h-full rounded-full transition-all duration-700 ease-out {{ $isFunded ? 'bg-forest shadow-[0_0_8px_rgba(45,106,79,0.4)]' : 'bg-gold shadow-[0_0_8px_rgba(197,160,89,0.3)]' }}"
                                     style="width: {{ $pct }}%"></div>
                            </div>
                        </div>

                        {{-- Funded Percentage --}}
                        <div class="text-right flex-shrink-0">
                            <p class="text-lg font-bold {{ $isFunded ? 'text-forest-light' : 'text-warm-white' }}">
                                {{ $pct }}%
                            </p>
                            <p class="text-[10px] text-muted uppercase tracking-tighter">Funded</p>
                        </div>

                        {{-- Edit link --}}
                        <a href="{{ route('buckets.edit', $bucket) }}" class="text-muted hover:text-gold transition-colors flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Excess Buckets --}}
        @if ($excessBuckets->count())
        <div class="mb-8">
            <h2 class="font-serif text-lg font-semibold text-warm-white mb-4 mt-2 flex items-center gap-2">
                <svg class="w-5 h-5 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Excess Buckets
            </h2>
            <div class="grid gap-3">
                @foreach ($excessBuckets as $bucket)
                    @php
                        $balance = (int) $bucket->transactions_sum_amount;
                        $capPct = $bucket->cap ? min(100, round($balance / $bucket->cap * 100)) : null;
                    @endphp
                    <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-4 flex items-center gap-4">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-surface flex items-center justify-center text-sm font-bold text-muted">
                            {{ $bucket->excess_percentage ?? '—' }}%
                        </div>

                        <div class="flex-1 min-w-0">
                            <a href="{{ route('buckets.show', $bucket) }}" class="text-warm-white font-semibold hover:text-gold transition-colors truncate block">
                                {{ $bucket->name }}
                                @if ($bucket->is_primary_savings)
                                    <span class="ml-1 text-xs bg-gold/20 text-gold px-1.5 py-0.5 rounded-full">Savings</span>
                                @endif
                            </a>
                            <div class="text-xs text-muted mt-0.5">
                                @if ($bucket->cap)
                                    Cap: ${{ number_format($bucket->cap / 100, 2) }}
                                @else
                                    No cap
                                @endif
                            </div>
                            @if ($capPct !== null)
                            <div class="mt-2 h-2 bg-surface rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-gold transition-all duration-700 ease-out shadow-[0_0_8px_rgba(197,160,89,0.3)]" style="width: {{ $capPct }}%"></div>
                            </div>
                            @endif
                        </div>

                        <div class="text-right flex-shrink-0">
                            <p class="text-lg font-bold {{ $balance >= 0 ? 'text-warm-white' : 'text-crimson' }}">
                                ${{ number_format($balance / 100, 2) }}
                            </p>
                        </div>

                        <a href="{{ route('buckets.edit', $bucket) }}" class="text-muted hover:text-gold transition-colors flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        @if ($buckets->isEmpty())
            <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 p-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-gold/10 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-muted mb-4">No buckets yet.</p>
                <a href="{{ route('buckets.create') }}" class="inline-block rounded-lg bg-gold px-5 py-2.5 text-sm font-semibold text-charcoal hover:bg-gold-hover transition-colors">
                    Create Your First Bucket
                </a>
            </div>
        @endif
    </div>

    {{-- Right: Action Pane --}}
    <div class="lg:w-96 flex-shrink-0" x-data="{ tab: 'deposit' }">
        <div class="rounded-xl bg-elevated shadow-lg shadow-black/20 sticky top-8">
            {{-- Tab Switcher --}}
            <div class="flex border-b border-border">
                <button @click="tab = 'deposit'" :class="tab === 'deposit' ? 'text-gold border-b-2 border-gold' : 'text-muted hover:text-warm-white'"
                    class="flex-1 px-4 py-3 text-sm font-semibold transition-colors">Deposit</button>
                <button @click="tab = 'expense'" :class="tab === 'expense' ? 'text-gold border-b-2 border-gold' : 'text-muted hover:text-warm-white'"
                    class="flex-1 px-4 py-3 text-sm font-semibold transition-colors">Expense</button>
                <button @click="tab = 'transfer'" :class="tab === 'transfer' ? 'text-crimson border-b-2 border-crimson' : 'text-muted hover:text-warm-white'"
                    class="flex-1 px-4 py-3 text-sm font-semibold transition-colors">Transfer</button>
            </div>

            <div class="p-5">
                {{-- Deposit Tab --}}
                <div x-show="tab === 'deposit'" x-cloak>
                    <form method="POST" action="{{ route('deposits.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="deposit_amount" class="block text-sm font-medium text-muted mb-1">Amount ($)</label>
                            <input type="number" name="amount" id="deposit_amount" step="0.01" min="0.01" required
                                value="{{ old('amount') }}"
                                class="w-full rounded-lg bg-surface border border-border text-warm-white px-4 py-3 text-2xl font-bold focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                                placeholder="0.00">
                            @error('amount') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="deposit_date" class="block text-sm font-medium text-muted mb-1">Date</label>
                            <input type="date" name="deposit_date" id="deposit_date" required
                                value="{{ old('deposit_date', date('Y-m-d')) }}"
                                class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold">
                            @error('deposit_date') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="deposit_desc" class="block text-sm font-medium text-muted mb-1">Description</label>
                            <input type="text" name="description" id="deposit_desc"
                                value="{{ old('description') }}"
                                class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                                placeholder="e.g. Paycheck">
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-gold to-gold-hover px-4 py-3 text-sm font-bold text-charcoal hover:bg-gold-hover transition-colors">
                            Fund Next in Stack
                        </button>
                    </form>
                </div>

                {{-- Expense Tab --}}
                <div x-show="tab === 'expense'" x-cloak>
                    <form method="POST" action="{{ route('expenses.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="expense_bucket" class="block text-sm font-medium text-muted mb-1">Bucket</label>
                            <select name="bucket_id" id="expense_bucket" required
                                class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold">
                                <option value="">Select a bucket...</option>
                                @foreach ($buckets as $bucket)
                                    @php $bal = (int) $bucket->transactions_sum_amount; @endphp
                                    <option value="{{ $bucket->id }}" {{ old('bucket_id') == $bucket->id ? 'selected' : '' }}>
                                        {{ $bucket->name }} (${{ number_format($bal / 100, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('bucket_id') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="expense_amount" class="block text-sm font-medium text-muted mb-1">Amount ($)</label>
                            <input type="number" name="amount" id="expense_amount" step="0.01" min="0.01" required
                                value="{{ old('amount') }}"
                                class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                                placeholder="e.g. 45.00">
                            @error('amount') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="expense_desc" class="block text-sm font-medium text-muted mb-1">Description</label>
                            <input type="text" name="description" id="expense_desc"
                                value="{{ old('description') }}"
                                class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-gold focus:border-gold placeholder-muted/50"
                                placeholder="e.g. Monthly water bill">
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-crimson px-4 py-3 text-sm font-bold text-white hover:bg-crimson-hover transition-colors">
                            Record Expense
                        </button>
                    </form>
                </div>

                {{-- Transfer Tab --}}
                <div x-show="tab === 'transfer'" x-cloak>
                    <div class="rounded-lg bg-crimson/10 border border-crimson/30 px-3 py-2 mb-4">
                        <p class="text-xs text-crimson font-semibold flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            Restricted Action — Danger Mode
                        </p>
                    </div>
                    <form method="POST" action="{{ route('transfers.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="transfer_source" class="block text-sm font-medium text-muted mb-1">From Bucket</label>
                            <select name="source_bucket_id" id="transfer_source" required
                                class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-crimson focus:border-crimson">
                                <option value="">Select source...</option>
                                @foreach ($buckets as $bucket)
                                    <option value="{{ $bucket->id }}" {{ old('source_bucket_id') == $bucket->id ? 'selected' : '' }}>
                                        {{ $bucket->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('source_bucket_id') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="transfer_dest" class="block text-sm font-medium text-muted mb-1">To Bucket</label>
                            <select name="destination_bucket_id" id="transfer_dest" required
                                class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-crimson focus:border-crimson">
                                <option value="">Select destination...</option>
                                @foreach ($buckets as $bucket)
                                    <option value="{{ $bucket->id }}" {{ old('destination_bucket_id') == $bucket->id ? 'selected' : '' }}>
                                        {{ $bucket->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('destination_bucket_id') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="transfer_amount" class="block text-sm font-medium text-muted mb-1">Amount ($)</label>
                            <input type="number" name="amount" id="transfer_amount" step="0.01" min="0.01" required
                                value="{{ old('amount') }}"
                                class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-crimson focus:border-crimson placeholder-muted/50"
                                placeholder="e.g. 200.00">
                            @error('amount') <p class="mt-1 text-xs text-crimson">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="transfer_desc" class="block text-sm font-medium text-muted mb-1">Description</label>
                            <input type="text" name="description" id="transfer_desc"
                                value="{{ old('description') }}"
                                class="w-full rounded-lg bg-surface border border-border text-warm-white px-3 py-2 text-sm focus:ring-2 focus:ring-crimson focus:border-crimson placeholder-muted/50"
                                placeholder="e.g. Emergency car repair">
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-crimson px-4 py-3 text-sm font-bold text-white hover:bg-crimson-hover transition-colors">
                            Execute Transfer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
