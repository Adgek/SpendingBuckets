@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('buckets.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Back to Buckets</a>
    </div>

    @php $balance = (int) $bucket->transactions_sum_amount; @endphp

    <div class="rounded-lg bg-white shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold text-gray-900">{{ $bucket->name }}</h1>
            <p class="text-2xl font-bold {{ $balance >= 0 ? 'text-green-700' : 'text-red-700' }}">
                ${{ number_format($balance / 100, 2) }}
            </p>
        </div>

        <dl class="grid grid-cols-2 gap-4 text-sm mb-6">
            <div>
                <dt class="text-gray-500">Type</dt>
                <dd class="font-medium text-gray-900">{{ ucfirst($bucket->type) }}</dd>
            </div>
            @if ($bucket->monthly_target)
                <div>
                    <dt class="text-gray-500">Monthly Target</dt>
                    <dd class="font-medium text-gray-900">${{ number_format($bucket->monthly_target / 100, 2) }}</dd>
                </div>
            @endif
            @if ($bucket->cap)
                <div>
                    <dt class="text-gray-500">Cap</dt>
                    <dd class="font-medium text-gray-900">${{ number_format($bucket->cap / 100, 2) }}</dd>
                </div>
            @endif
            @if ($bucket->priority_order !== null)
                <div>
                    <dt class="text-gray-500">Priority</dt>
                    <dd class="font-medium text-gray-900">{{ $bucket->priority_order }}</dd>
                </div>
            @endif
        </dl>

        <h2 class="text-lg font-semibold text-gray-900 mb-3">Transaction History</h2>
        <div class="divide-y">
            @forelse ($bucket->transactions as $txn)
                <div class="py-3 flex items-center justify-between">
                    <div>
                        <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium
                            {{ match($txn->type) {
                                'allocation' => 'bg-green-100 text-green-800',
                                'expense' => 'bg-red-100 text-red-800',
                                'transfer' => 'bg-yellow-100 text-yellow-800',
                                'sweep' => 'bg-blue-100 text-blue-800',
                                default => 'bg-gray-100 text-gray-800',
                            } }}">
                            {{ ucfirst($txn->type) }}
                        </span>
                        <span class="ml-2 text-sm text-gray-600">{{ $txn->description ?? '—' }}</span>
                    </div>
                    <span class="font-mono text-sm {{ $txn->amount >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        {{ $txn->amount >= 0 ? '+' : '' }}${{ number_format($txn->amount / 100, 2) }}
                    </span>
                </div>
            @empty
                <p class="py-3 text-sm text-gray-500">No transactions yet.</p>
            @endforelse
        </div>
    </div>
@endsection
