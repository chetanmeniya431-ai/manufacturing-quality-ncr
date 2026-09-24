<div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
    <form wire:submit="login" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" wire:model="email" autofocus>
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" wire:model="password">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="text-right">
            <a href="{{ route('password.request') }}" class="text-xs text-amber-600 hover:text-amber-700">Forgot password?</a>
        </div>
        <button type="submit" wire:loading.attr="disabled" wire:target="login" class="w-full inline-flex justify-center items-center rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="login">Sign in</span>
            <span wire:loading wire:target="login">Signing in…</span>
        </button>
    </form>

    <div class="mt-6 pt-6 border-t border-gray-100 text-xs text-gray-500 space-y-1">
        <p class="font-medium text-gray-600">Demo logins (password: <code>password</code>)</p>
        <p>admin@qualitymanager.local — Quality Manager</p>
        <p>inspector@qualitymanager.local — Quality Inspector</p>
        <p>production@qualitymanager.local — Production Manager</p>
        <p>supplier@qualitymanager.local — Supplier</p>
        <p>auditor@qualitymanager.local — Auditor</p>
    </div>
</div>
