<div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
    @if($sent)
        <div class="text-center py-4">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
            </div>
            <h2 class="text-base font-semibold text-gray-900">Check your email</h2>
            <p class="mt-1 text-sm text-gray-500">If that address is registered, a reset link has been sent.</p>
        </div>
    @else
        <h2 class="text-base font-semibold text-gray-900 mb-1">Forgot your password?</h2>
        <p class="text-sm text-gray-500 mb-5">Enter your email and we'll send a reset link.</p>

        <form wire:submit="send" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" wire:model="email" autofocus autocomplete="username">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="send" class="w-full inline-flex justify-center items-center rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="send">Send reset link</span>
                <span wire:loading wire:target="send">Sending…</span>
            </button>
        </form>
    @endif

    <div class="mt-5 text-center">
        <a href="{{ route('login') }}" class="text-sm text-amber-600 hover:text-amber-700">← Back to sign in</a>
    </div>
</div>
