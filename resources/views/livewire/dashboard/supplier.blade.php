<div>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">{{ $supplier?->name }}</h1>
        <p class="text-sm text-gray-500">Supplier portal — NCRs linked to your account</p>
    </div>

    @if($supplier)
    <div class="bg-white border border-gray-200 rounded-xl p-5 mb-6 inline-flex items-center gap-4">
        <div>
            <p class="text-xs text-gray-500">Current quality score</p>
            <p class="text-3xl font-semibold text-gray-900">{{ $supplier->qualityScore() }}</p>
        </div>
        <x-badge :color="$supplier->scoreColor() === 'green' ? 'green' : ($supplier->scoreColor() === 'yellow' ? 'yellow' : 'red')">
            {{ ucfirst($supplier->scoreColor()) }}
        </x-badge>
    </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-xl">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Your NCRs</h2>
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
                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('ncrs.show', $ncr) }}'">
                            <td class="px-5 py-3 font-medium text-amber-700">{{ $ncr->ncr_number }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $ncr->product_name }}</td>
                            <td class="px-5 py-3"><x-badge :color="$ncr->severityBadgeColor()">{{ \App\Models\Ncr::SEVERITY_LABELS[$ncr->severity] }}</x-badge></td>
                            <td class="px-5 py-3"><x-badge :color="$ncr->statusBadgeColor()">{{ \App\Models\Ncr::STATUS_LABELS[$ncr->status] }}</x-badge></td>
                            <td class="px-5 py-3 text-gray-500">{{ $ncr->detected_date->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">No NCRs linked to your account.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
