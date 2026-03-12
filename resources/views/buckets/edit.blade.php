@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <a href="/buckets" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Back to Buckets</a>
    </div>

    <div class="rounded-lg bg-white shadow p-6 max-w-lg">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit: {{ $bucket->name }}</h1>

        <form method="POST" action="/buckets/{{ $bucket->id }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $bucket->name) }}" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                <select name="type" id="type" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="fixed" {{ old('type', $bucket->type) === 'fixed' ? 'selected' : '' }}>Fixed</option>
                    <option value="excess" {{ old('type', $bucket->type) === 'excess' ? 'selected' : '' }}>Excess</option>
                </select>
                @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="monthly_target" class="block text-sm font-medium text-gray-700">Monthly Target (cents)</label>
                <input type="number" name="monthly_target" id="monthly_target" value="{{ old('monthly_target', $bucket->monthly_target) }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                @error('monthly_target') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="priority_order" class="block text-sm font-medium text-gray-700">Priority Order</label>
                <input type="number" name="priority_order" id="priority_order" value="{{ old('priority_order', $bucket->priority_order) }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                @error('priority_order') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="cap" class="block text-sm font-medium text-gray-700">Cap (cents, optional)</label>
                <input type="number" name="cap" id="cap" value="{{ old('cap', $bucket->cap) }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                @error('cap') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="excess_percentage" class="block text-sm font-medium text-gray-700">Excess Percentage</label>
                <input type="number" name="excess_percentage" id="excess_percentage" value="{{ old('excess_percentage', $bucket->excess_percentage) }}" min="0" max="100"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                @error('excess_percentage') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="sweeps_excess" value="1" {{ old('sweeps_excess', $bucket->sweeps_excess) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Sweeps Excess
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_primary_savings" value="1" {{ old('is_primary_savings', $bucket->is_primary_savings) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Primary Savings
                </label>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Update Bucket
                </button>
            </div>
        </form>

        <form method="POST" action="/buckets/{{ $bucket->id }}" class="mt-6 border-t pt-4">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-red-600 hover:text-red-800"
                onclick="return confirm('Are you sure you want to delete this bucket?')">
                Delete this bucket
            </button>
        </form>
    </div>
@endsection
