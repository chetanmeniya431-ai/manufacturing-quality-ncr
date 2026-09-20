<div>
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Suppliers</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($suppliers as $supplier)
            @php
                $ringClass = match($supplier->scoreColor()) {
                    'green' => 'border-green-200',
                    'yellow' => 'border-yellow-200',
                    default => 'border-red-200',
                };
                $textClass = match($supplier->scoreColor()) {
                    'green' => 'text-green-700',
                    'yellow' => 'text-yellow-700',
                    default => 'text-red-700',
                };
            @endphp
            <a href="{{ route('suppliers.show', $supplier) }}" class="bg-white border {{ $ringClass }} rounded-xl p-5 hover:shadow-sm transition">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium text-gray-900">{{ $supplier->name }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $supplier->contact_name }}</p>
                    </div>
                    @unless($supplier->approved)
                        <x-badge color="gray">Not approved</x-badge>
                    @endunless
                </div>
                <div class="mt-4 flex items-end justify-between">
                    <div>
                        <p class="text-2xl font-semibold {{ $textClass }}">{{ $supplier->qualityScore() }}</p>
                        <p class="text-xs text-gray-400">Quality score</p>
                    </div>
                    <p class="text-xs text-gray-500">{{ $supplier->ncrs()->count() }} NCR(s) total</p>
                </div>
            </a>
        @endforeach
    </div>
</div>
