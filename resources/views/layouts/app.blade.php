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
            <a href="{{ route('dashboard') }}" class="text-gold font-serif text-xl font-bold mb-4" title="Spending Buckets">SB</a>

            <nav class="flex flex-col items-center gap-4 flex-1">
                @php $isDashboard = request()->routeIs('dashboard'); @endphp
                <a href="{{ route('dashboard') }}" class="group flex flex-col items-center gap-1 transition-colors relative {{ $isDashboard ? 'text-gold' : 'text-muted hover:text-gold' }}" title="Dashboard">
                    @if ($isDashboard)
                        <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-6 h-0.5 bg-gold rounded-full"></span>
                    @endif
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span class="text-[10px]">Dashboard</span>
                </a>
                @php $isBuckets = request()->routeIs('buckets.*'); @endphp
                <a href="{{ route('buckets.index') }}" class="group flex flex-col items-center gap-1 transition-colors relative {{ $isBuckets ? 'text-gold' : 'text-muted hover:text-gold' }}" title="Buckets">
                    @if ($isBuckets)
                        <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-6 h-0.5 bg-gold rounded-full"></span>
                    @endif
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span class="text-[10px]">Buckets</span>
                </a>
                @php $isDeposits = request()->routeIs('deposits.*'); @endphp
                <a href="{{ route('deposits.index') }}" class="group flex flex-col items-center gap-1 transition-colors relative {{ $isDeposits ? 'text-gold' : 'text-muted hover:text-gold' }}" title="Deposits">
                    @if ($isDeposits)
                        <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-6 h-0.5 bg-gold rounded-full"></span>
                    @endif
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                    <span class="text-[10px]">Deposits</span>
                </a>
                @php $isSweep = request()->routeIs('sweep.*'); @endphp
                <a href="{{ route('sweep.create') }}" class="group flex flex-col items-center gap-1 transition-colors relative {{ $isSweep ? 'text-gold' : 'text-muted hover:text-gold' }}" title="Sweep">
                    @if ($isSweep)
                        <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-6 h-0.5 bg-gold rounded-full"></span>
                    @endif
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span class="text-[10px]">Sweep</span>
                </a>
            </nav>
        </aside>

        {{-- Mobile Bottom Bar --}}
        <nav class="md:hidden fixed bottom-0 inset-x-0 bg-navy border-t border-border z-30 flex items-center justify-around py-2">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-0.5 text-muted hover:text-gold text-[10px]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
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

        </nav>

        {{-- Main Content --}}
        <main class="flex-1 md:ml-20 pb-20 md:pb-0">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
                @if (session('success'))
                    <div x-data="{ show: true }"
                         x-show="show"
                         x-init="setTimeout(() => show = false, 5000)"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="mb-6 rounded-xl bg-forest/15 backdrop-blur-sm px-4 py-3 text-sm text-forest-light flex items-center justify-between">
                        <span>{{ session('success') }}</span>
                        <button @click="show = false" class="text-forest-light/60 hover:text-forest-light ml-4">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div x-data="{ show: true }"
                         x-show="show"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="mb-6 rounded-xl bg-crimson/15 backdrop-blur-sm px-4 py-3 text-sm text-crimson flex items-center justify-between">
                        <span>{{ session('error') }}</span>
                        <button @click="show = false" class="text-crimson/60 hover:text-crimson ml-4">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
