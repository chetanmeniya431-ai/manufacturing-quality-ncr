<div>
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <h1 class="text-xl font-semibold text-gray-900">Non-Conformance Reports</h1>
        <div class="flex items-center gap-2">
            @if(!auth()->user()->isReadOnly() && !auth()->user()->isSupplier())
                <a href="{{ route('reports') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <x-icon name="download" class="w-4 h-4" /> Export Audit Report
                </a>
            @endif
            @if(auth()->user()->canManageNcrs())
                <a href="{{ route('ncrs.import') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <x-icon name="upload" class="w-4 h-4" /> Bulk Import
                </a>
                <a href="{{ route('ncrs.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                    <x-icon name="plus" class="w-4 h-4" /> Log NCR
                </a>
            @endif
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl mb-4 p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search NCR #, product, description...">
        <select wire:model.live="status">
            <option value="">All statuses</option>
            @foreach(\App\Models\Ncr::STATUS_LABELS as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="severity">
            <option value="">All severities</option>
            @foreach(\App\Models\Ncr::SEVERITY_LABELS as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="defect_category">
            <option value="">All defect categories</option>
            @foreach(\App\Models\Ncr::DEFECT_CATEGORY_LABELS as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        @if(!auth()->user()->isSupplier())
        <select wire:model.live="supplier_id">
            <option value="">All suppliers</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
            @endforeach
        </select>
        @endif
    </div>

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-100 bg-gray-50">
                        <th class="px-5 py-3 font-medium">NCR #</th>
                        <th class="px-5 py-3 font-medium">Product</th>
                        <th class="px-5 py-3 font-medium">Category</th>
                        <th class="px-5 py-3 font-medium">Severity</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Detected</th>
                        <th class="px-5 py-3 font-medium">Assigned to</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($ncrs as $ncr)
                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('ncrs.show', $ncr) }}'">
                            <td class="px-5 py-3 font-medium text-amber-700 whitespace-nowrap">{{ $ncr->ncr_number }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $ncr->product_name }}</td>
                            <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ \App\Models\Ncr::DEFECT_CATEGORY_LABELS[$ncr->defect_category] }}</td>
                            <td class="px-5 py-3"><x-badge :color="$ncr->severityBadgeColor()">{{ \App\Models\Ncr::SEVERITY_LABELS[$ncr->severity] }}</x-badge></td>
                            <td class="px-5 py-3"><x-badge :color="$ncr->statusBadgeColor()">{{ \App\Models\Ncr::STATUS_LABELS[$ncr->status] }}</x-badge></td>
                            <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $ncr->detected_date->format('d M Y') }}</td>
                            <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $ncr->correctiveActionAssignee?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">No NCRs match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-gray-100">
            {{ $ncrs->links() }}
        </div>
    </div>
</div>
