<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Magic Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="h-full">
    <div class="min-h-full">
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8 flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-900 tracking-tight">
                    <span class="text-indigo-600">Magic</span> Generator
                </h1>
                <span class="text-xs text-gray-400">v1.1</span>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <livewire:magic-generator />
        </main>
    </div>
    @livewireScripts
</body>
</html>
