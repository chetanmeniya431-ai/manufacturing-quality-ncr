<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-gray-900 antialiased">
    <div class="min-h-full flex items-center justify-center px-4">
        <div class="w-full max-w-sm">
            <div class="flex flex-col items-center mb-8">
                <div class="w-12 h-12 rounded-xl bg-amber-600 flex items-center justify-center text-white font-bold text-xl">Q</div>
                <h1 class="mt-4 text-lg font-semibold text-gray-900">Quality NCR Manager</h1>
                <p class="text-sm text-gray-500">Vantage Precision Parts Ltd</p>
            </div>
            {{ $slot }}
        </div>
    </div>
    @livewireScripts
</body>
</html>
