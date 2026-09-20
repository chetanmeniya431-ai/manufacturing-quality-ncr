<div class="max-w-xl">
    <div class="mb-6">
        <a href="{{ route('ncrs.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to NCRs</a>
        <h1 class="text-xl font-semibold text-gray-900 mt-1">Bulk Import NCRs</h1>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-6 space-y-5">
        <div class="flex items-start gap-3 bg-gray-50 border border-gray-200 rounded-lg p-4">
            <x-icon name="document-text" class="w-5 h-5 text-gray-400 shrink-0" />
            <div class="text-sm text-gray-600">
                <p>Download the CSV template, fill in one row per NCR, then upload it below.</p>
                <a href="{{ route('ncrs.template') }}" class="inline-flex items-center gap-1 mt-2 text-amber-700 font-medium hover:underline">
                    <x-icon name="download" class="w-4 h-4" /> Download sample template
                </a>
            </div>
        </div>

        <form wire:submit="import" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CSV file</label>
                <input type="file" wire:model="file" accept=".csv,text/csv">
                @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700" wire:loading.attr="disabled" wire:target="import">
                <span wire:loading.remove wire:target="import">Import</span>
                <span wire:loading wire:target="import">Importing...</span>
            </button>
        </form>

        @if($created !== null)
            <div class="border-t border-gray-100 pt-4">
                <p class="text-sm font-medium text-green-700">{{ $created }} NCR(s) created.</p>
                @if(count($errors))
                    <ul class="mt-2 text-xs text-red-600 space-y-1">
                        @foreach($errors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </div>
</div>
