<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-gray-900 antialiased">
    <div class="min-h-full flex" x-data="{ mobileNavOpen: false }">
        {{-- Mobile top bar --}}
        <div class="lg:hidden fixed top-0 inset-x-0 z-40 flex items-center justify-between bg-white border-b border-gray-200 px-4 py-3">
            <a href="{{ route('dashboard') }}" class="font-semibold text-amber-700">Quality NCR Manager</a>
            <button @click="mobileNavOpen = !mobileNavOpen" class="p-2 text-gray-500">
                <x-icon name="menu" class="w-6 h-6" />
            </button>
        </div>

        {{-- Sidebar --}}
        <aside
            x-cloak
            :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed lg:static z-30 top-0 left-0 h-full w-64 bg-white border-r border-gray-200 flex flex-col transition-transform duration-200 pt-16 lg:pt-0"
        >
            <div class="hidden lg:flex items-center gap-2 px-6 h-16 border-b border-gray-200">
                <div class="w-8 h-8 rounded-lg bg-amber-600 flex items-center justify-center text-white font-bold">Q</div>
                <span class="font-semibold text-gray-900 text-sm leading-tight">Quality NCR<br>Manager</span>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-amber-50 text-amber-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <x-icon name="home" class="w-5 h-5" /> Dashboard
                </a>
                <a href="{{ route('ncrs.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('ncrs.*') ? 'bg-amber-50 text-amber-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <x-icon name="clipboard" class="w-5 h-5" /> NCRs
                </a>
                @if(!auth()->user()->isSupplier())
                <a href="{{ route('suppliers.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('suppliers.*') ? 'bg-amber-50 text-amber-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <x-icon name="truck" class="w-5 h-5" /> Suppliers
                </a>
                <a href="{{ route('documents.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('documents.*') ? 'bg-amber-50 text-amber-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <x-icon name="document-text" class="w-5 h-5" /> Quality Documents
                </a>
                <a href="{{ route('assistant') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('assistant') ? 'bg-amber-50 text-amber-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <x-icon name="chat" class="w-5 h-5" /> AI Assistant
                </a>
                <a href="{{ route('signals.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('signals.*') ? 'bg-amber-50 text-amber-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <x-icon name="bell-alert" class="w-5 h-5" /> Signals
                </a>
                <a href="{{ route('reports') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('reports') ? 'bg-amber-50 text-amber-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <x-icon name="chart-bar" class="w-5 h-5" /> Reports
                </a>
                @endif
                @if(auth()->user()->isQualityManager())
                <a href="{{ route('settings') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('settings') ? 'bg-amber-50 text-amber-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <x-icon name="cog" class="w-5 h-5" /> Settings
                </a>
                @endif
            </nav>

            <div class="px-3 py-4 border-t border-gray-200">
                <div class="flex items-center gap-3 px-3 py-2">
                    <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-sm font-medium text-amber-700">
                        {{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->getRoleNames()->first() ? ucwords(str_replace('_', ' ', auth()->user()->getRoleNames()->first())) : '' }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="mt-1 w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">
                        <x-icon name="logout" class="w-5 h-5" />
                        Sign out
                    </button>
                </form>
            </div>
        </aside>

        <div class="lg:hidden fixed inset-0 bg-gray-900/40 z-20" x-show="mobileNavOpen" x-cloak @click="mobileNavOpen = false"></div>

        {{-- Main content --}}
        <main class="flex-1 min-w-0 pt-16 lg:pt-0">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                @if(session('status'))
                    <div class="mb-6 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3">
                        {{ session('status') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-6 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3">
                        {{ session('error') }}
                    </div>
                @endif
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>
