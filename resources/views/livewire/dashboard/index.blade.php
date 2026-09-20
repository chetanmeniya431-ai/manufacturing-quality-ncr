<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
            <p class="text-sm text-gray-500">Vantage Precision Parts Ltd — ISO 9001 Quality Overview</p>
        </div>
        @if(auth()->user()->canManageNcrs())
        <a href="{{ route('ncrs.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
            <x-icon name="plus" class="w-4 h-4" /> Log NCR
        </a>
        @endif
    </div>

    {{-- Status counts --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-8">
        @foreach([
            'open' => ['Open', 'gray'],
            'investigating' => ['Investigating', 'blue'],
            'corrective_assigned' => ['Corrective Action', 'amber'],
            'verification' => ['Verification', 'purple'],
            'closed' => ['Closed', 'green'],
        ] as $key => [$label, $color])
            <a href="{{ route('ncrs.index', ['status' => $key]) }}" class="bg-white border border-gray-200 rounded-xl p-4 hover:border-amber-300 transition">
                <p class="text-2xl font-semibold text-gray-900">{{ $counts[$key] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $label }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Signal alerts --}}
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">Open Signal Events</h2>
                <a href="{{ route('signals.index') }}" class="text-xs font-medium text-amber-700 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($openSignalEvents as $event)
                    <div class="px-5 py-3 flex items-start gap-3">
                        <x-badge :color="$event->signal->severityBadgeColor()">{{ ucfirst($event->signal->severity) }}</x-badge>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $event->signal->name }}</p>
                            <p class="text-xs text-gray-500">
                                @if($event->ncr)
                                    <a href="{{ route('ncrs.show', $event->ncr) }}" class="hover:underline">{{ $event->ncr->ncr_number }}</a>
                                @elseif($event->supplier)
                                    <a href="{{ route('suppliers.show', $event->supplier) }}" class="hover:underline">{{ $event->supplier->name }}</a>
                                @elseif($event->context)
                                    {{ collect($event->context)->map(fn($v,$k) => "$k: $v")->implode(', ') }}
                                @endif
                                · {{ $event->triggered_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-sm text-gray-400 text-center">No open signal events. Everything looks on track.</p>
                @endforelse
            </div>
        </div>

        {{-- Supplier scores --}}
        <div class="bg-white border border-gray-200 rounded-xl">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Supplier Quality Scores</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($suppliers as $supplier)
                    @php
                        $dotClass = match($supplier->scoreColor()) {
                            'green' => 'bg-green-500',
                            'yellow' => 'bg-yellow-500',
                            default => 'bg-red-500',
                        };
                    @endphp
                    <a href="{{ route('suppliers.show', $supplier) }}" class="px-5 py-3 flex items-center justify-between hover:bg-gray-50">
                        <span class="text-sm text-gray-800 truncate">{{ $supplier->name }}</span>
                        <span class="flex items-center gap-1.5 text-sm font-semibold">
                            <span class="w-2 h-2 rounded-full {{ $dotClass }}"></span>
                            {{ $supplier->qualityScore() }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Recent NCRs --}}
    <div class="mt-6 bg-white border border-gray-200 rounded-xl">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Recently Logged NCRs</h2>
            <a href="{{ route('ncrs.index') }}" class="text-xs font-medium text-amber-700 hover:underline">View all</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-100">
                        <th class="px-5 py-2 font-medium">NCR #</th>
                        <th class="px-5 py-2 font-medium">Product</th>
                        <th class="px-5 py-2 font-medium">Severity</th>
                        <th class="px-5 py-2 font-medium">Status</th>
                        <th class="px-5 py-2 font-medium">Detected</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($recentNcrs as $ncr)
                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('ncrs.show', $ncr) }}'">
                            <td class="px-5 py-3 font-medium text-amber-700">{{ $ncr->ncr_number }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $ncr->product_name }}</td>
                            <td class="px-5 py-3"><x-badge :color="$ncr->severityBadgeColor()">{{ \App\Models\Ncr::SEVERITY_LABELS[$ncr->severity] }}</x-badge></td>
                            <td class="px-5 py-3"><x-badge :color="$ncr->statusBadgeColor()">{{ \App\Models\Ncr::STATUS_LABELS[$ncr->status] }}</x-badge></td>
                            <td class="px-5 py-3 text-gray-500">{{ $ncr->detected_date->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
