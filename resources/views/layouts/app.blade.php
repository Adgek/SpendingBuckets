<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Spending Buckets' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-charcoal min-h-screen text-warm-white" x-data="{ mobileMenu: false }">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="hidden md:flex md:w-20 flex-col items-center bg-navy border-r border-border py-6 gap-6 fixed inset-y-0 left-0 z-30">
            <a href="{{ route('buckets.index') }}" class="text-gold font-serif text-xl font-bold mb-4" title="Spending Buckets">SB</a>

            <nav class="flex flex-col items-center gap-4 flex-1">
                <a href="{{ route('buckets.index') }}" class="group flex flex-col items-center gap-1 text-muted hover:text-gold transition-colors" title="Buckets">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span class="text-[10px]">Buckets</span>
                </a>
                <a href="{{ route('deposits.index') }}" class="group flex flex-col items-center gap-1 text-muted hover:text-gold transition-colors" title="Deposits">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                    <span class="text-[10px]">Deposits</span>
                </a>
                <a href="{{ route('sweep.create') }}" class="group flex flex-col items-center gap-1 text-muted hover:text-gold transition-colors" title="Sweep">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span class="text-[10px]">Sweep</span>
                </a>
            </nav>

            <a href="{{ route('buckets.create') }}" class="text-muted hover:text-gold transition-colors" title="New Bucket">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </a>
        </aside>

        {{-- Mobile Bottom Bar --}}
        <nav class="md:hidden fixed bottom-0 inset-x-0 bg-navy border-t border-border z-30 flex items-center justify-around py-2">
            <a href="{{ route('buckets.index') }}" class="flex flex-col items-center gap-0.5 text-muted hover:text-gold text-[10px]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Buckets
            </a>
            <a href="{{ route('deposits.index') }}" class="flex flex-col items-center gap-0.5 text-muted hover:text-gold text-[10px]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                Deposits
            </a>
            <a href="{{ route('sweep.create') }}" class="flex flex-col items-center gap-0.5 text-muted hover:text-gold text-[10px]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Sweep
            </a>
            <a href="{{ route('buckets.create') }}" class="flex flex-col items-center gap-0.5 text-muted hover:text-gold text-[10px]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                New
            </a>
        </nav>

        {{-- Main Content --}}
        <main class="flex-1 md:ml-20 pb-20 md:pb-0">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
                @if (session('success'))
                    <div class="mb-6 rounded-lg bg-forest/20 border border-forest px-4 py-3 text-sm text-forest-light">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 rounded-lg bg-crimson/20 border border-crimson px-4 py-3 text-sm text-crimson">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
