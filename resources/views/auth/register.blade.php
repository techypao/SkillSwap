<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | SkillSwap</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">

        <!-- Logo / Title -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                SkillSwap
            </h1>

            <p class="text-gray-500 mt-2">
                Create your account and start exchanging skills.
            </p>
        </div>


        <!-- Validation Errors -->
        @if ($errors->any())

            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-lg p-4">

                <ul class="text-sm space-y-1">

                    @foreach ($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif


        <!-- Registration Form -->
        <form method="POST" action="{{ route('register.store') }}">

            @csrf


            <!-- Full Name -->
            <div class="mb-5">

                <label
                    for="name"
                    class="block text-sm font-medium text-gray-700 mb-2"
                >
                    Full Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus

                    placeholder="Enter your full name"

                    class="w-full px-4 py-3 border border-gray-300 rounded-lg
                           focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >

            </div>


            <!-- Email -->
            <div class="mb-5">

                <label
                    for="email"
                    class="block text-sm font-medium text-gray-700 mb-2"
                >
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required

                    placeholder="you@example.com"

                    class="w-full px-4 py-3 border border-gray-300 rounded-lg
                           focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >

            </div>


            <!-- Password -->
            <div class="mb-5">

                <label
                    for="password"
                    class="block text-sm font-medium text-gray-700 mb-2"
                >
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required

                    placeholder="Minimum 8 characters"

                    class="w-full px-4 py-3 border border-gray-300 rounded-lg
                           focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >

            </div>


            <!-- Confirm Password -->
            <div class="mb-6">

                <label
                    for="password_confirmation"
                    class="block text-sm font-medium text-gray-700 mb-2"
                >
                    Confirm Password
                </label>

                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required

                    placeholder="Repeat your password"

                    class="w-full px-4 py-3 border border-gray-300 rounded-lg
                           focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >

            </div>


            <!-- Register Button -->
            <button
                type="submit"

                class="w-full bg-indigo-600 text-white py-3 rounded-lg
                       font-semibold hover:bg-indigo-700 transition"
            >
                Create Account
            </button>


        </form>


        <!-- Login Link -->
        <p class="text-center text-sm text-gray-500 mt-6">

            Already have an account?

           <a
            href="{{ route('login') }}"
            class="text-indigo-600 font-medium hover:underline"
            >
            Login
        </a>

        </p>

    </div>

</body>

</html>