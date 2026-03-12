@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('buckets.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Back to Buckets</a>
    </div>

    <div class="rounded-lg bg-white shadow p-6 max-w-lg">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Record Expense</h1>

        <form method="POST" action="{{ route('expenses.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="bucket_id" class="block text-sm font-medium text-gray-700">Bucket</label>
                <select name="bucket_id" id="bucket_id" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">Select a bucket...</option>
                    @foreach ($buckets as $bucket)
                        <option value="{{ $bucket->id }}" {{ old('bucket_id') == $bucket->id ? 'selected' : '' }}>
                            {{ $bucket->name }}
                        </option>
                    @endforeach
                </select>
                @error('bucket_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700">Amount ($)</label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" required min="0.01" step="0.01"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    placeholder="e.g. 45.00">
                @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description (optional)</label>
                <input type="text" name="description" id="description" value="{{ old('description') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                Record Expense
            </button>
        </form>
    </div>
@endsection
