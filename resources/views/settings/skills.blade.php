<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Settings | SkillSwap</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100">
<div class="min-h-screen px-4 py-10">
    <div class="max-w-4xl mx-auto">

        @include('settings.partials.header', ['active' => 'skills'])

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Skills</h1>
            <p class="text-gray-600 mt-2">
                Update what you can teach and what you want to learn. Each section saves separately.
            </p>
        </div>

        @if (session('success'))
            <div class="mb-6 bg-green-100 text-green-700 px-4 py-3 rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        {{-- Teaching Skills --}}
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <form action="{{ route('settings.skills.teaching.update') }}" method="POST">
                @csrf
                @method('PATCH')

                @include('partials.skill-picker', [
                    'group' => 'teaching',
                    'heading' => 'What can you teach?',
                    ...$teaching,
                ])

                <button
                    type="submit"
                    class="w-full bg-gray-900 text-white font-semibold py-3 px-4 rounded-xl
                           hover:bg-gray-800 transition"
                >
                    Save Teaching Skills
                </button>
            </form>
        </div>

        {{-- Learning Skills --}}
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <form action="{{ route('settings.skills.learning.update') }}" method="POST">
                @csrf
                @method('PATCH')

                @include('partials.skill-picker', [
                    'group' => 'learning',
                    'heading' => 'What do you want to learn?',
                    ...$learning,
                ])

                <button
                    type="submit"
                    class="w-full bg-gray-900 text-white font-semibold py-3 px-4 rounded-xl
                           hover:bg-gray-800 transition"
                >
                    Save Learning Goals
                </button>
            </form>
        </div>

    </div>
</div>

@stack('scripts')
</body>
</html>
