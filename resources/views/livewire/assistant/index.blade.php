<div class="max-w-3xl" @if($hasPending) wire:poll.3s @endif>
    <h1 class="text-xl font-semibold text-gray-900 mb-1">Quality Assistant</h1>
    <p class="text-sm text-gray-500 mb-6">Ask questions about your quality manual, product specs, and other uploaded documents.</p>

    <div class="space-y-4 pb-24">
        @forelse($conversation as $turn)
            <div class="flex justify-end">
                <div class="bg-amber-600 text-white rounded-2xl rounded-br-sm px-4 py-2.5 max-w-lg text-sm">
                    {{ $turn->question }}
                </div>
            </div>
            <div class="flex justify-start">
                <div class="bg-white border border-gray-200 rounded-2xl rounded-bl-sm px-4 py-3 max-w-lg text-sm text-gray-800">
                    @if($turn->status === 'pending' && $turn->isStale())
                        <p class="text-amber-600">This is taking much longer than usual — something may be wrong.</p>
                        <button wire:click="retry({{ $turn->id }})" wire:loading.attr="disabled" wire:target="retry({{ $turn->id }})"
                                class="mt-2 rounded-md border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50 disabled:opacity-50">
                            <span wire:loading.remove wire:target="retry({{ $turn->id }})">Retry</span>
                            <span wire:loading wire:target="retry({{ $turn->id }})">Retrying…</span>
                        </button>
                    @elseif($turn->status === 'pending')
                        <p class="text-gray-400">Thinking... this can take a couple of minutes.</p>
                    @elseif($turn->status === 'failed')
                        <p class="text-red-600">The AI assistant is temporarily unavailable.</p>
                        <button wire:click="retry({{ $turn->id }})" wire:loading.attr="disabled" wire:target="retry({{ $turn->id }})"
                                class="mt-2 rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 disabled:opacity-50">
                            <span wire:loading.remove wire:target="retry({{ $turn->id }})">Retry</span>
                            <span wire:loading wire:target="retry({{ $turn->id }})">Retrying…</span>
                        </button>
                    @else
                        <p class="whitespace-pre-line">{{ $turn->answer }}</p>
                        @if(count($turn->sources ?? []))
                            <div class="mt-2 pt-2 border-t border-gray-100 flex flex-wrap gap-1.5">
                                @foreach($turn->sources as $source)
                                    <span class="text-xs bg-gray-100 text-gray-500 rounded-full px-2 py-0.5">
                                        {{ $source['document'] }}@if($source['page']), p.{{ $source['page'] }}@endif
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-16 text-gray-400">
                <x-icon name="chat" class="w-10 h-10 mx-auto mb-3 text-gray-300" />
                <p class="text-sm">Try asking: "Does our quality manual require a CAPA for a major defect?"</p>
            </div>
        @endforelse
    </div>

    <div class="sticky bottom-0 bg-gray-50 pt-3 pb-6">
        <form wire:submit="ask" class="flex items-center gap-2">
            <input type="text" wire:model="question" placeholder="Ask a question about your quality documents..." class="flex-1" wire:loading.attr="disabled" wire:target="ask">
            <button type="submit" class="shrink-0 inline-flex items-center justify-center rounded-lg bg-amber-600 w-11 h-11 text-white hover:bg-amber-700 disabled:opacity-50" wire:loading.attr="disabled" wire:target="ask">
                <x-icon name="send" class="w-5 h-5" wire:loading.remove wire:target="ask" />
                <svg wire:loading wire:target="ask" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </button>
        </form>
        @error('question') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
