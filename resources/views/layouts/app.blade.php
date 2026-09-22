<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
@php $isDemo = auth()->check() && !auth()->user()->hasRole('Super Admin'); @endphp
<body class="h-full font-sans text-gray-900 antialiased" style="{{ $isDemo ? 'padding-top: 40px' : '' }}">
@if($isDemo)
<div class="fixed top-0 inset-x-0 z-[60] bg-amber-500 text-white text-sm font-medium py-2 px-4 flex items-center justify-between gap-4">
    <div class="flex items-center gap-2">
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <span>You are viewing a <strong>live demo</strong>. Actions that add or change data are disabled.</span>
    </div>
    <button onclick="if(window.Livewire)Livewire.dispatch('show-demo-modal')" class="shrink-0 rounded-md bg-white/20 hover:bg-white/30 px-3 py-1 text-xs font-semibold transition-colors">
        Get your own system →
    </button>
</div>
@endif
    <div class="min-h-full flex" x-data="{ mobileNavOpen: false }">
        {{-- Mobile top bar --}}
        <div class="lg:hidden fixed inset-x-0 z-40 flex items-center justify-between bg-white border-b border-gray-200 px-4 py-3"
             style="{{ $isDemo ? 'top: 40px' : 'top: 0' }}">
            <a href="{{ route('dashboard') }}" class="font-semibold text-amber-700">Quality NCR Manager</a>
            <button @click="mobileNavOpen = !mobileNavOpen" class="p-2 text-gray-500">
                <x-icon name="menu" class="w-6 h-6" />
            </button>
        </div>

        {{-- Sidebar --}}
        <aside
            x-cloak
            :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed lg:static z-30 left-0 h-full w-64 bg-white border-r border-gray-200 flex flex-col transition-transform duration-200 pt-16 lg:pt-0"
            style="{{ $isDemo ? 'top: 40px' : 'top: 0' }}"
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

                @role('Super Admin')
                    <div class="pt-4 mt-4 border-t border-gray-200 space-y-1">
                        <a href="{{ route('super-admin.contacts') }}"
                           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('super-admin.*') ? 'bg-amber-50 text-amber-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <x-icon name="envelope" class="w-5 h-5" />
                            Contact Requests
                            @php $newCount = \App\Models\ContactRequest::where('status','new')->count(); @endphp
                            @if($newCount > 0)
                                <span class="ml-auto inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-500 text-xs font-bold text-white">{{ $newCount }}</span>
                            @endif
                        </a>
                    </div>
                @endrole
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
            <livewire:demo-contact-modal />
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

@if($isDemo)
<script>
(function () {
    /* ================================================================
     * DEMO MODE GUARD v2 — fetch interception
     *
     * Intercepts every Livewire POST before it hits the server:
     *   - demo-contact-modal requests  → allow (contact form works)
     *   - poll ($refresh) / model-only → allow (page stays interactive)
     *   - write method calls            → show modal, return proper no-op
     *
     * The no-op echoes each component's snapshot unchanged so loading
     * indicators clear and the page stays intact.
     * ================================================================ */

    var WRITE_METHODS = [
        'create','save','delete','store','update','upload','submit',
        'add','remove','approve','reject','attach','detach','assign',
        'confirm','destroy','complete','fail','mark','process','archive',
        'activate','deactivate','reset','publish','verify','resolve',
        'retry','toggle','import','send','generate','record','log'
    ];

    function isWriteCall(name) {
        if (!name || name === '$refresh') return false;
        var lc = name.toLowerCase();
        return WRITE_METHODS.some(function (k) { return lc.indexOf(k) !== -1; });
    }

    function showDemoModal() {
        if (window.Livewire) { window.Livewire.dispatch('show-demo-modal'); }
    }

    var _fetch = window.fetch;
    window.fetch = function (url, opts) {
        var urlStr = typeof url === 'string' ? url : (url && url.href ? url.href : String(url));
        var isLwUpdate = opts && opts.method === 'POST' && urlStr.indexOf('livewire/update') !== -1;
        var isLwUpload = opts && opts.method === 'POST' && urlStr.indexOf('livewire/upload') !== -1;

        if (!isLwUpdate && !isLwUpload) return _fetch.apply(this, arguments);

        if (isLwUpload) {
            showDemoModal();
            return Promise.resolve(new Response(
                JSON.stringify({ message: 'Demo mode' }),
                { status: 422, headers: { 'Content-Type': 'application/json' } }
            ));
        }

        var body = '';
        try { body = opts.body ? String(opts.body) : ''; } catch (e) {}

        // Always allow the DemoContactModal (contact form + show/close actions)
        if (body.indexOf('demo-contact-modal') !== -1) return _fetch.apply(this, arguments);

        // Allow poll-only and model-only updates (no explicit write calls)
        var hasWrite = false;
        try {
            var d = JSON.parse(body);
            if (Array.isArray(d.components)) {
                hasWrite = d.components.some(function (c) {
                    return (c.calls || []).some(function (call) { return isWriteCall(call.method); });
                });
            }
        } catch (e) {}

        if (!hasWrite) return _fetch.apply(this, arguments);

        // Write request: show modal and return a proper no-op so loading states clear
        showDemoModal();
        try {
            var req = JSON.parse(body);
            var noOp = (req.components || []).map(function (c) {
                try {
                    var snap = JSON.parse(c.snapshot);
                    return { id: snap.memo.id, snapshot: c.snapshot,
                             effects: { html: null, returns: {}, dispatches: [], xjs: [] } };
                } catch (e2) { return null; }
            }).filter(Boolean);
            return Promise.resolve(new Response(
                JSON.stringify({ components: noOp, assets: [] }),
                { status: 200, headers: { 'Content-Type': 'application/json' } }
            ));
        } catch (e) {}

        return Promise.resolve(new Response(
            JSON.stringify({ components: [], assets: [] }),
            { status: 200, headers: { 'Content-Type': 'application/json' } }
        ));
    };

    // Regular HTML form submissions (non-Livewire, non-logout, non-GET)
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form) return;
        if (form.method && form.method.toLowerCase() === 'get') return;
        if (form.action && form.action.indexOf('logout') !== -1) return;
        if (form.getAttribute && form.getAttribute('wire:submit')) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        showDemoModal();
    }, true);
})();
</script>
@endif
</body>
</html>
