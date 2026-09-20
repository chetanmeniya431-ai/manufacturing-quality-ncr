<div class="max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('ncrs.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to NCRs</a>
        <div class="flex items-center justify-between mt-1 flex-wrap gap-2">
            <h1 class="text-xl font-semibold text-gray-900">{{ $ncr->ncr_number }} — {{ $ncr->product_name }}</h1>
            <div class="flex items-center gap-2">
                <x-badge :color="$ncr->severityBadgeColor()">{{ \App\Models\Ncr::SEVERITY_LABELS[$ncr->severity] }}</x-badge>
                <x-badge :color="$ncr->statusBadgeColor()">{{ \App\Models\Ncr::STATUS_LABELS[$ncr->status] }}</x-badge>
            </div>
        </div>
    </div>

    {{-- Workflow progress --}}
    <div class="flex items-center gap-1 mb-6">
        @foreach(\App\Models\Ncr::STATUS_ORDER as $i => $step)
            @php
                $currentIndex = array_search($ncr->status, \App\Models\Ncr::STATUS_ORDER, true);
                $done = $i <= $currentIndex;
            @endphp
            <div class="flex-1 h-1.5 rounded-full {{ $done ? 'bg-amber-500' : 'bg-gray-200' }}"></div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Details --}}
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Details</h2>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500">Defect category</dt><dd class="text-gray-900 mt-0.5">{{ \App\Models\Ncr::DEFECT_CATEGORY_LABELS[$ncr->defect_category] }}</dd></div>
                    <div><dt class="text-gray-500">Where detected</dt><dd class="text-gray-900 mt-0.5">{{ \App\Models\Ncr::DETECTED_AT_LABELS[$ncr->detected_at] }}</dd></div>
                    <div><dt class="text-gray-500">Detected by</dt><dd class="text-gray-900 mt-0.5">{{ $ncr->detectedBy->name }}</dd></div>
                    <div><dt class="text-gray-500">Detected date</dt><dd class="text-gray-900 mt-0.5">{{ $ncr->detected_date->format('d M Y') }}</dd></div>
                    @if($ncr->supplier)
                    <div><dt class="text-gray-500">Supplier</dt><dd class="text-gray-900 mt-0.5"><a href="{{ route('suppliers.show', $ncr->supplier) }}" class="text-amber-700 hover:underline">{{ $ncr->supplier->name }}</a></dd></div>
                    @endif
                    <div><dt class="text-gray-500">Logged by</dt><dd class="text-gray-900 mt-0.5">{{ $ncr->createdBy->name }} on {{ $ncr->created_at->format('d M Y') }}</dd></div>
                </dl>
                <div class="mt-4">
                    <dt class="text-gray-500 text-sm">Description</dt>
                    <dd class="text-gray-900 mt-1 whitespace-pre-line">{{ $ncr->description }}</dd>
                </div>
                @if(!empty($ncr->attachments))
                <div class="mt-4">
                    <dt class="text-gray-500 text-sm mb-1">Attachments</dt>
                    <ul class="text-sm text-amber-700 space-y-0.5">
                        @foreach($ncr->attachments as $path)
                            <li>{{ basename($path) }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>

            {{-- AI similar NCRs + suggestion --}}
            @if(!auth()->user()->isSupplier())
            <div
                class="bg-white border border-gray-200 rounded-xl p-6"
                @if($ncr->ai_suggestion_status === 'pending') wire:poll.3s="checkAiSuggestion" @endif
            >
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-900">Similar Past NCRs & AI Root Cause Suggestion</h2>
                    @if(in_array($ncr->ai_suggestion_status, ['none', 'failed']) && auth()->user()->canManageNcrs())
                        <button wire:click="loadAiSuggestions" class="text-xs font-medium text-amber-700 hover:underline">
                            {{ $ncr->ai_suggestion_status === 'failed' ? 'Retry' : 'Find similar NCRs' }}
                        </button>
                    @endif
                </div>

                @if($ncr->ai_suggestion_status === 'pending')
                    <p class="text-sm text-gray-400">Analyzing with AI... this can take a couple of minutes.</p>
                @elseif($ncr->ai_suggestion_status === 'failed')
                    <p class="text-sm text-red-600">AI suggestions are temporarily unavailable. Please try again shortly.</p>
                @elseif($aiLoaded)
                    @if(count($similarCases) > 0)
                        <div class="space-y-3 mb-4">
                            @foreach($similarCases as $case)
                                <a href="{{ route('ncrs.show', $case['ncr']) }}" class="block border border-gray-100 rounded-lg p-3 hover:border-amber-300">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-amber-700">{{ $case['ncr']->ncr_number }}</span>
                                        <span class="text-xs text-gray-400">{{ round($case['similarity'] * 100) }}% similar</span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ $case['ncr']->description }}</p>
                                    <p class="text-xs text-gray-500 mt-1"><span class="font-medium">Root cause:</span> {{ $case['ncr']->root_cause ?: 'not recorded' }}</p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <p class="text-xs font-medium text-amber-800 mb-1">AI suggestion (verify before using)</p>
                        <p class="text-sm text-amber-900">{{ $ncr->ai_root_cause_suggestion }}</p>
                    </div>
                @else
                    <p class="text-sm text-gray-400">Not analyzed yet.</p>
                @endif
            </div>
            @endif

            {{-- Workflow action forms --}}
            @if(auth()->user()->canManageNcrs() && $ncr->status !== 'closed')
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Advance Workflow</h2>

                @if($ncr->status === 'open')
                    <p class="text-sm text-gray-500 mb-3">Start the investigation for this NCR.</p>
                    <button wire:click="moveToInvestigating" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                        Move to Under Investigation
                    </button>
                @endif

                @if($ncr->status === 'investigating')
                    <form wire:submit="assignCorrectiveAction" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Root cause</label>
                            <textarea wire:model="root_cause" rows="3" placeholder="What is the root cause of this non-conformance?"></textarea>
                            @error('root_cause') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Corrective action</label>
                            <textarea wire:model="corrective_action" rows="3" placeholder="What corrective action will resolve this?"></textarea>
                            @error('corrective_action') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Assign to</label>
                                <select wire:model="corrective_action_assigned_to">
                                    <option value="">Select...</option>
                                    @foreach($assignees as $assignee)
                                        <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                                    @endforeach
                                </select>
                                @error('corrective_action_assigned_to') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Due date</label>
                                <input type="date" wire:model="corrective_action_due">
                                @error('corrective_action_due') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                            Assign Corrective Action
                        </button>
                    </form>
                @endif

                @if($ncr->status === 'corrective_assigned')
                    <p class="text-sm text-gray-500 mb-3">Once the corrective action has been implemented, move this NCR to verification.</p>
                    <button wire:click="moveToVerification" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                        Move to Verification
                    </button>
                @endif

                @if($ncr->status === 'verification')
                    <form wire:submit="closeNcr" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Verification notes</label>
                            <textarea wire:model="verification_notes" rows="3" placeholder="How was the corrective action verified?"></textarea>
                            @error('verification_notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @if(auth()->user()->canCloseNcrs())
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Closure note</label>
                                <textarea wire:model="closure_notes" rows="3" placeholder="Summary for closure..."></textarea>
                                @error('closure_notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model="verification_confirmed" class="w-auto">
                                I confirm the corrective action was verified effective.
                            </label>
                            @error('verification_confirmed') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                                Close NCR
                            </button>
                        @else
                            <p class="text-xs text-gray-400">Only a Quality Manager can close this NCR.</p>
                        @endif
                    </form>
                @endif
            </div>
            @endif

            @if($ncr->status === 'closed')
                <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                    <h2 class="text-sm font-semibold text-green-900 mb-2">Closed</h2>
                    <p class="text-sm text-green-800"><span class="font-medium">Root cause:</span> {{ $ncr->root_cause }}</p>
                    <p class="text-sm text-green-800 mt-1"><span class="font-medium">Corrective action:</span> {{ $ncr->corrective_action }}</p>
                    <p class="text-sm text-green-800 mt-1"><span class="font-medium">Verification:</span> {{ $ncr->verification_notes }}</p>
                    <p class="text-sm text-green-800 mt-1"><span class="font-medium">Closure note:</span> {{ $ncr->closure_notes }}</p>
                    <p class="text-xs text-green-600 mt-2">Closed by {{ $ncr->closedBy?->name }} on {{ $ncr->closed_at?->format('d M Y') }}</p>
                </div>
            @endif

            @if(auth()->user()->isSupplier())
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">Submit a Response</h2>
                    <form wire:submit="submitSupplierResponse" class="space-y-3">
                        <textarea wire:model="supplier_response" rows="3" placeholder="Add your response for the quality team..."></textarea>
                        @error('supplier_response') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                            Submit Response
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- Status history --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6 h-fit">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Status History</h2>
            <ul class="space-y-4">
                @foreach($ncr->statusHistory as $entry)
                    <li class="text-sm">
                        @if($entry->old_status === $entry->new_status)
                            <p class="font-medium text-gray-900">Note added</p>
                        @else
                            <p class="font-medium text-gray-900">{{ \App\Models\Ncr::STATUS_LABELS[$entry->new_status] ?? $entry->new_status }}</p>
                        @endif
                        <p class="text-xs text-gray-500">{{ $entry->changedBy->name }} · {{ $entry->created_at->format('d M Y, H:i') }}</p>
                        @if($entry->note)
                            <p class="text-xs text-gray-600 mt-1">{{ $entry->note }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
