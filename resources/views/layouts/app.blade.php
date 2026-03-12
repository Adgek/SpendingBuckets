<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Spending Buckets' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center gap-6">
            <a href="/buckets" class="text-lg font-bold text-gray-900">Spending Buckets</a>
            <a href="/buckets" class="text-sm text-gray-600 hover:text-gray-900">Buckets</a>
            <a href="/deposits" class="text-sm text-gray-600 hover:text-gray-900">Deposits</a>
            <a href="/expenses/create" class="text-sm text-gray-600 hover:text-gray-900">Record Expense</a>
            <a href="/transfers/create" class="text-sm text-gray-600 hover:text-gray-900">Transfer</a>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 py-8">
        @if (session('success'))
            <div class="mb-6 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
