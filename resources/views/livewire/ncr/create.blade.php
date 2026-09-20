<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('ncrs.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to NCRs</a>
        <h1 class="text-xl font-semibold text-gray-900 mt-1">Log a Non-Conformance Report</h1>
    </div>

    <form wire:submit="save" class="bg-white border border-gray-200 rounded-xl p-6 space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Product or process name</label>
            <input type="text" wire:model="product_name" placeholder="e.g. Precision Shaft Type A">
            @error('product_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Defect category</label>
                <select wire:model="defect_category">
                    <option value="">Select...</option>
                    @foreach(\App\Models\Ncr::DEFECT_CATEGORY_LABELS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('defect_category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                <select wire:model="severity">
                    <option value="">Select...</option>
                    @foreach(\App\Models\Ncr::SEVERITY_LABELS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('severity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea wire:model="description" rows="4" placeholder="Describe the non-conformance in detail..."></textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Where detected</label>
                <select wire:model="detected_at">
                    <option value="">Select...</option>
                    @foreach(\App\Models\Ncr::DETECTED_AT_LABELS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('detected_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Detected date</label>
                <input type="date" wire:model="detected_date">
                @error('detected_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Related supplier (if supplier defect)</label>
            <select wire:model="supplier_id">
                <option value="">None</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
            @error('supplier_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Attachments (photos, measurement records)</label>
            <input type="file" wire:model="attachments" multiple>
            <div wire:loading wire:target="attachments" class="text-xs text-gray-400 mt-1">Uploading...</div>
            @error('attachments.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @if($attachments)
                <ul class="mt-2 text-xs text-gray-500 space-y-0.5">
                    @foreach($attachments as $file)
                        <li>{{ $file->getClientOriginalName() }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="pt-2 flex items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Log NCR</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
            <a href="{{ route('ncrs.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</div>
