<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | SkillSwap</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">

        <!-- Header -->
        <div class="text-center mb-8">

            <h1 class="text-3xl font-bold text-gray-900">
                SkillSwap
            </h1>

            <p class="text-gray-500 mt-2">
                Welcome back! Sign in to continue.
            </p>

        </div>


        <!-- Errors -->
        @if ($errors->any())

            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-lg p-4">

                @foreach ($errors->all() as $error)

                    <p class="text-sm">
                        {{ $error }}
                    </p>

                @endforeach

            </div>

        @endif


        <!-- Login Form -->
        <form method="POST" action="{{ route('login.store') }}">

            @csrf


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
                    autofocus

                    placeholder="you@example.com"

                    class="w-full px-4 py-3 border border-gray-300 rounded-lg
                           focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >

            </div>


            <!-- Password -->
            <div class="mb-4">

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

                    placeholder="Enter your password"

                    class="w-full px-4 py-3 border border-gray-300 rounded-lg
                           focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >

            </div>


            <!-- Remember Me -->
            <div class="flex items-center mb-6">

                <input
                    type="checkbox"
                    id="remember"
                    name="remember"

                    class="w-4 h-4"
                >

                <label
                    for="remember"
                    class="ml-2 text-sm text-gray-600"
                >
                    Remember me
                </label>

            </div>


            <!-- Login Button -->
            <button
                type="submit"

                class="w-full bg-indigo-600 text-white py-3 rounded-lg
                       font-semibold hover:bg-indigo-700 transition"
            >
                Login
            </button>

        </form>


        <!-- Register -->
        <p class="text-center text-sm text-gray-500 mt-6">

            Don't have an account?

            <a
                href="{{ route('register') }}"
                class="text-indigo-600 font-medium hover:underline"
            >
                Create an account
            </a>

        </p>

    </div>

</body>

</html>