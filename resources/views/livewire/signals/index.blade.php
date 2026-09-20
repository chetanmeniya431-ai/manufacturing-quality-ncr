<div>
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <h1 class="text-xl font-semibold text-gray-900">Signals</h1>
        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" wire:model.live="showResolved" class="w-auto">
            Show resolved
        </label>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl divide-y divide-gray-100">
            @forelse($events as $event)
                <div class="px-5 py-4 flex items-start gap-3" wire:key="event-{{ $event->id }}">
                    <x-badge :color="$event->signal->severityBadgeColor()">{{ ucfirst($event->signal->severity) }}</x-badge>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $event->signal->name }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            @if($event->ncr)
                                <a href="{{ route('ncrs.show', $event->ncr) }}" class="hover:underline text-amber-700">{{ $event->ncr->ncr_number }}</a> — {{ $event->ncr->product_name }}
                            @elseif($event->supplier)
                                <a href="{{ route('suppliers.show', $event->supplier) }}" class="hover:underline text-amber-700">{{ $event->supplier->name }}</a>
                            @elseif($event->context)
                                {{ collect($event->context)->map(fn($v,$k) => ucfirst(str_replace('_',' ',$k)).": $v")->implode(' · ') }}
                            @endif
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            Triggered {{ $event->triggered_at->diffForHumans() }}
                            @if($event->resolved_at)
                                · Resolved {{ $event->resolved_at->diffForHumans() }} by {{ $event->resolvedBy?->name ?? 'system' }}
                            @endif
                        </p>
                        @if($event->note)
                            <p class="text-xs text-gray-500 mt-1">{{ $event->note }}</p>
                        @endif
                    </div>
                    @if(!$event->resolved_at && auth()->user()->canManageNcrs())
                        <button wire:click="resolve({{ $event->id }})" class="text-xs font-medium text-amber-700 hover:underline shrink-0">
                            Resolve
                        </button>
                    @endif
                </div>
            @empty
                <p class="px-5 py-10 text-center text-gray-400 text-sm">No signal events.</p>
            @endforelse
        </div>

        <div class="bg-white border border-gray-200 rounded-xl">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Signal Definitions</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($signals as $signal)
                    @php($fields = $signal->configurableFields())
                    <div class="px-5 py-3" wire:key="signal-{{ $signal->id }}">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-medium text-gray-900">{{ $signal->name }}</p>
                            @if(auth()->user()->isQualityManager())
                                <button wire:click="toggleSignal({{ $signal->id }})" class="text-xs {{ $signal->active ? 'text-green-700' : 'text-gray-400' }}">
                                    {{ $signal->active ? 'Active' : 'Inactive' }}
                                </button>
                            @else
                                <x-badge :color="$signal->active ? 'green' : 'gray'">{{ $signal->active ? 'Active' : 'Inactive' }}</x-badge>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $signal->description }}</p>

                        @if(!empty($fields))
                            @if($editingSignalId === $signal->id)
                                <div class="mt-2 space-y-2 bg-gray-50 rounded-lg p-3">
                                    @foreach($fields as $field => $config)
                                        <label class="flex items-center justify-between gap-2 text-xs text-gray-600">
                                            {{ $config['label'] }}
                                            <span class="flex items-center gap-1">
                                                <input
                                                    type="number"
                                                    wire:model="editValues.{{ $field }}"
                                                    min="{{ $config['min'] }}"
                                                    max="{{ $config['max'] }}"
                                                    class="w-20 text-xs py-1"
                                                >
                                                @if($config['unit'])
                                                    <span class="text-gray-400">{{ $config['unit'] }}</span>
                                                @endif
                                            </span>
                                        </label>
                                        @error('editValues.'.$field)
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    @endforeach
                                    <div class="flex items-center gap-3 pt-1">
                                        <button wire:click="saveSignal({{ $signal->id }})" class="text-xs font-medium text-amber-700 hover:underline">
                                            Save
                                        </button>
                                        <button wire:click="cancelEdit" class="text-xs text-gray-500 hover:underline">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="mt-1 flex items-center justify-between gap-2">
                                    <p class="text-xs text-gray-400">
                                        {{ collect($fields)->map(fn($c, $f) => $c['label'].': '.$signal->$f.($c['unit'] ? ' '.$c['unit'] : ''))->implode(' · ') }}
                                    </p>
                                    @if(auth()->user()->isQualityManager())
                                        <button wire:click="editSignal({{ $signal->id }})" class="text-xs text-amber-700 hover:underline shrink-0">
                                            Edit
                                        </button>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
