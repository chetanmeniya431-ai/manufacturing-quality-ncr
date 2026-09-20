<div>
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Quality Documents</h1>

    @if(auth()->user()->canManageNcrs())
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Upload a document</h2>
        <form wire:submit="upload" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-700 mb-1">Name</label>
                <input type="text" wire:model="name" placeholder="e.g. ISO 9001:2015 Quality Manual">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                <select wire:model="document_type">
                    @foreach(\App\Models\QualityDocument::TYPE_LABELS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">PDF file</label>
                <input type="file" wire:model="file" accept="application/pdf">
                @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-4">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700" wire:loading.attr="disabled" wire:target="upload,file">
                    <x-icon name="upload" class="w-4 h-4" />
                    <span wire:loading.remove wire:target="upload">Upload</span>
                    <span wire:loading wire:target="upload">Uploading...</span>
                </button>
            </div>
        </form>
    </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-100 bg-gray-50">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Type</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Chunks</th>
                    <th class="px-5 py-3 font-medium">Uploaded by</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($documents as $document)
                    <tr wire:key="doc-{{ $document->id }}">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $document->name }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ \App\Models\QualityDocument::TYPE_LABELS[$document->document_type] }}</td>
                        <td class="px-5 py-3">
                            @php
                                $statusColor = match($document->status) {
                                    'ready' => 'green',
                                    'processing', 'pending' => 'amber',
                                    'failed' => 'red',
                                    default => 'gray',
                                };
                            @endphp
                            <x-badge :color="$statusColor">{{ ucfirst($document->status) }}</x-badge>
                            @if($document->status === 'failed' && $document->error_message)
                                <p class="text-xs text-red-500 mt-1">{{ $document->error_message }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $document->chunk_count }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $document->createdBy->name }}</td>
                        <td class="px-5 py-3 text-right">
                            @if(auth()->user()->canManageNcrs())
                                <button wire:click="delete({{ $document->id }})" wire:confirm="Delete this document and its chunks?" class="text-gray-400 hover:text-red-600">
                                    <x-icon name="trash" class="w-4 h-4" />
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No quality documents uploaded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
