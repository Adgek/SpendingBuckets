@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Deposits</h1>
        <a href="{{ route('deposits.create') }}" class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">New Deposit</a>
    </div>

    <div class="rounded-lg bg-white shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($deposits as $deposit)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $deposit->deposit_date->format('M j, Y') }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-green-700">${{ number_format($deposit->amount / 100, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $deposit->description ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-sm text-gray-500 text-center">No deposits yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
