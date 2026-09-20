<div class="max-w-xl">
    <h1 class="text-xl font-semibold text-gray-900 mb-1">Audit Report</h1>
    <p class="text-sm text-gray-500 mb-6">Generate a complete, organised evidence pack for ISO auditors.</p>

    <div class="bg-white border border-gray-200 rounded-xl p-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
            <select wire:model.live="period">
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
                <option value="180">Last 180 days</option>
                <option value="custom">Custom range</option>
            </select>
        </div>

        @if($period === 'custom')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                    <input type="date" wire:model.live="from">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                    <input type="date" wire:model.live="to">
                </div>
            </div>
        @endif

        <p class="text-sm text-gray-500">{{ $previewCount }} NCR(s) will be included in this report.</p>

        <a href="{{ $this->downloadUrl }}" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700">
            <x-icon name="download" class="w-4 h-4" /> Download Audit Report (PDF)
        </a>
    </div>

    <div class="mt-6 bg-gray-50 border border-gray-200 rounded-xl p-4 text-xs text-gray-500">
        <p class="font-medium text-gray-600 mb-1">The report includes:</p>
        <ul class="list-disc list-inside space-y-0.5">
            <li>Summary of NCRs by status, severity, and category for this period</li>
            <li>Full NCR list with all fields</li>
            <li>Corrective actions with completion status</li>
            <li>Supplier quality scores</li>
            <li>All open signal events</li>
            <li>List of uploaded quality documents</li>
        </ul>
    </div>
</div>
