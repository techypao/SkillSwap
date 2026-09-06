<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Welcome to SkillSwap</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100">

    <div class="min-h-screen flex items-center justify-center px-4">

        <div class="bg-white rounded-2xl shadow-lg p-8 max-w-lg w-full text-center">

            {{-- Step Indicator --}}
            <p class="text-sm text-gray-500 mb-3">
                Step 1 of 4
            </p>

            {{-- Title --}}
            <h1 class="text-3xl font-bold text-gray-900 mb-4">
                Welcome to SkillSwap!
            </h1>

            {{-- Description --}}
            <p class="text-gray-600 mb-8">
                Share what you know, learn something new,
                and connect with people who want to grow with you.
            </p>

            {{-- User Greeting --}}
            <div class="bg-gray-50 rounded-xl p-4 mb-8">

                <p class="text-gray-700">
                    Hello,
                    <span class="font-semibold">
                        {{ auth()->user()->name }}
                    </span>
                    👋
                </p>

                <p class="text-sm text-gray-500 mt-1">
                    Let's set up your SkillSwap profile.
                </p>

            </div>

            {{-- Continue Button --}}
            <a
                href="{{ route('onboarding.profile') }}"
                class="block w-full bg-gray-900 text-white font-semibold
                       py-3 px-6 rounded-xl hover:bg-gray-800 transition"
            >
                Get Started
            </a>

        </div>

    </div>

</body>
</html>