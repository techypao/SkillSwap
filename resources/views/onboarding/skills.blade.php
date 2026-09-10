<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} | SkillSwap</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100">
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="bg-white rounded-2xl shadow-lg p-8 max-w-4xl w-full">
        <div class="mb-6">
            <p class="text-sm text-gray-500">Step {{ $step }} of 5</p>
            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-gray-900 h-2 rounded-full" style="width: {{ $step * 20 }}%;"></div>
            </div>
        </div>

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ $title }}</h1>
            <p class="text-gray-600 mt-2">{{ $intro }}</p>
        </div>

        <form action="{{ $formAction }}" method="POST">
            @csrf

            @include('partials.skill-picker', [
                'group' => $group,
                'heading' => $heading,
                'skills' => $skills,
                'categories' => $categories,
                'selected' => $selected,
            ])

            <div class="flex items-center gap-3">
                <a
                    href="{{ $backRoute }}"
                    class="w-1/3 text-center border border-gray-300 text-gray-700 font-semibold py-3 px-4 rounded-xl hover:bg-gray-100 transition"
                >
                    Back
                </a>
                <button
                    type="submit"
                    class="w-2/3 bg-gray-900 text-white font-semibold py-3 px-4 rounded-xl hover:bg-gray-800 transition"
                >
                    Save & Continue
                </button>
            </div>
        </form>
    </div>
</div>

@stack('scripts')
</body>
</html>
