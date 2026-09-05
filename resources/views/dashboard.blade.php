<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | SkillSwap</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-100">

    <div class="max-w-5xl mx-auto py-10 px-6">

        <div class="bg-white shadow rounded-2xl p-8">

            <div class="flex justify-between items-center">

                <div>

                    <h1 class="text-3xl font-bold">
                        SkillSwap Dashboard
                    </h1>

                    <p class="text-gray-600 mt-2">
                        Welcome, {{ auth()->user()->name }}!
                    </p>

                    <p class="text-sm text-gray-500 mt-1">
                        Role: {{ auth()->user()->role }}
                    </p>

                </div>


                <!-- Logout -->
                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >

                    @csrf

                    <button
                        type="submit"
                        class="bg-red-500 hover:bg-red-600 text-white
                               px-5 py-2 rounded-lg"
                    >
                        Logout
                    </button>

                </form>

            </div>

        </div>

    </div>

</body>

</html>