@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('buckets.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Back to Buckets</a>
    </div>

    <div class="rounded-lg bg-white shadow p-6 max-w-lg">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Record Deposit</h1>

        <form method="POST" action="{{ route('deposits.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700">Amount ($)</label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" required min="0.01" step="0.01"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    placeholder="e.g. 1500.00">
                @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="deposit_date" class="block text-sm font-medium text-gray-700">Deposit Date</label>
                <input type="date" name="deposit_date" id="deposit_date" value="{{ old('deposit_date', date('Y-m-d')) }}" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                @error('deposit_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description (optional)</label>
                <input type="text" name="description" id="description" value="{{ old('description') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                Process Deposit
            </button>
        </form>
    </div>
@endsection
