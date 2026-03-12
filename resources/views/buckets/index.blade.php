@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Buckets</h1>
        <div class="flex gap-3">
            <a href="{{ route('deposits.create') }}" class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">New Deposit</a>
            <a href="{{ route('buckets.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">New Bucket</a>
        </div>
    </div>

    <div class="grid gap-4">
        @forelse ($buckets as $bucket)
            @php $balance = (int) $bucket->transactions_sum_amount; @endphp
            <div class="rounded-lg bg-white shadow p-5 flex items-center justify-between">
                <div>
                    <a href="{{ route('buckets.show', $bucket) }}" class="text-lg font-semibold text-gray-900 hover:text-indigo-600">{{ $bucket->name }}</a>
                    <p class="text-sm text-gray-500 mt-1">
                        <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $bucket->type === 'fixed' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                            {{ ucfirst($bucket->type) }}
                        </span>
                        @if ($bucket->monthly_target)
                            &middot; Target: ${{ number_format($bucket->monthly_target / 100, 2) }}
                        @endif
                        @if ($bucket->cap)
                            &middot; Cap: ${{ number_format($bucket->cap / 100, 2) }}
                        @endif
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xl font-bold {{ $balance >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        ${{ number_format($balance / 100, 2) }}
                    </p>
                    <div class="flex gap-2 mt-2">
                        <a href="{{ route('buckets.edit', $bucket) }}" class="text-xs text-gray-500 hover:text-indigo-600">Edit</a>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-gray-500">No buckets yet. Create one to get started.</p>
        @endforelse
    </div>
@endsection
