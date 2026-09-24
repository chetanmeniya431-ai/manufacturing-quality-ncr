<div class="max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('suppliers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Suppliers</a>
        <div class="flex items-center justify-between mt-1 flex-wrap gap-2">
            <h1 class="text-xl font-semibold text-gray-900">{{ $supplier->name }}</h1>
            @if(auth()->user()->canManageNcrs())
                <button wire:click="toggleApproved" class="text-xs font-medium text-gray-500 hover:text-gray-700 underline">
                    {{ $supplier->approved ? 'Mark as not approved' : 'Mark as approved' }}
                </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <p class="text-xs text-gray-500">Quality score</p>
            <p class="text-4xl font-semibold text-gray-900 mt-1">{{ $supplier->qualityScore() }}</p>
            <x-badge :color="$supplier->scoreColor() === 'green' ? 'green' : ($supplier->scoreColor() === 'yellow' ? 'yellow' : 'red')" class="mt-2">
                {{ $supplier->approved ? 'Approved' : 'Not approved' }}
            </x-badge>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-6 lg:col-span-2">
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500">Contact</dt><dd class="text-gray-900 mt-0.5">{{ $supplier->contact_name ?: '—' }}</dd></div>
                <div><dt class="text-gray-500">Email</dt><dd class="text-gray-900 mt-0.5">{{ $supplier->contact_email ?: '—' }}</dd></div>
                <div class="col-span-2">
                    <dt class="text-gray-500">Product categories</dt>
                    <dd class="text-gray-900 mt-0.5">{{ collect($supplier->product_categories ?? [])->implode(', ') ?: '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-6 bg-white border border-gray-200 rounded-xl">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">NCRs from this supplier</h2>
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
                    @forelse($ncrs as $ncr)
                        <tr class="hover:bg-gray-50 cursor-pointer" x-data x-on:click="window.location = '{{ route('ncrs.show', $ncr) }}'">
                            <td class="px-5 py-3 font-medium text-amber-700">{{ $ncr->ncr_number }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $ncr->product_name }}</td>
                            <td class="px-5 py-3"><x-badge :color="$ncr->severityBadgeColor()">{{ \App\Models\Ncr::SEVERITY_LABELS[$ncr->severity] }}</x-badge></td>
                            <td class="px-5 py-3"><x-badge :color="$ncr->statusBadgeColor()">{{ \App\Models\Ncr::STATUS_LABELS[$ncr->status] }}</x-badge></td>
                            <td class="px-5 py-3 text-gray-500">{{ $ncr->detected_date->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">No NCRs recorded for this supplier.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
