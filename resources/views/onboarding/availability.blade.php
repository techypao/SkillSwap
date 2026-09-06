<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Availability | SkillSwap</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>


<body class="bg-gray-100">

<div class="min-h-screen flex items-center justify-center px-4 py-10">

    <div class="bg-white rounded-2xl shadow-lg p-8 max-w-4xl w-full">


        {{-- Progress --}}

        <div class="mb-6">

            <p class="text-sm text-gray-500">
                Step 4 of 4
            </p>

            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">

                <div
                    class="bg-gray-900 h-2 rounded-full"
                    style="width: 100%;"
                ></div>

            </div>

        </div>


        {{-- Heading --}}

        <div class="mb-8">

            <h1 class="text-3xl font-bold text-gray-900">
                When are you available?
            </h1>

            <p class="text-gray-600 mt-2">
                Select the days and times when you're usually
                available for a SkillSwap session.
            </p>

        </div>


        {{-- Skills Saved Message --}}

        @if(session('success'))

            <div
                class="mb-6 bg-green-100 text-green-700
                       px-4 py-3 rounded-xl"
            >
                {{ session('success') }}
            </div>

        @endif


        {{-- Availability Validation Error --}}

        @error('availability')

            <div
                class="mb-6 bg-red-100 text-red-700
                       px-4 py-3 rounded-xl"
            >
                {{ $message }}
            </div>

        @enderror


        @php

            $days = [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            ];

            $timePeriods = [
                'morning',
                'afternoon',
                'evening',
            ];

        @endphp


        <form
            action="{{ route('onboarding.availability.store') }}"
            method="POST"
        >

            @csrf


            {{-- Availability Table --}}

            <div class="overflow-x-auto">

                <table class="w-full border-collapse">

                    <thead>

                    <tr>

                        <th
                            class="text-left py-4 px-4
                                   text-gray-600"
                        >
                            Day
                        </th>

                        @foreach($timePeriods as $timePeriod)

                            <th
                                class="text-center py-4 px-4
                                       text-gray-600"
                            >
                                {{ ucfirst($timePeriod) }}
                            </th>

                        @endforeach

                    </tr>

                    </thead>


                    <tbody>

                    @foreach($days as $day)

                        <tr class="border-t border-gray-200">

                            <td
                                class="py-5 px-4
                                       font-semibold text-gray-800"
                            >
                                {{ ucfirst($day) }}
                            </td>


                            @foreach($timePeriods as $timePeriod)

                                @php

                                    $key =
                                        $day . '_' . $timePeriod;

                                    $oldValue = old(
                                        "availability.$day.$timePeriod",
                                        in_array(
                                            $key,
                                            $selectedAvailability
                                        )
                                    );

                                @endphp


                                <td class="py-5 px-4 text-center">

                                    <input
                                        type="checkbox"

                                        name="availability[{{ $day }}][{{ $timePeriod }}]"

                                        value="1"

                                        class="w-5 h-5 cursor-pointer"

                                        @checked($oldValue)
                                    >

                                </td>

                            @endforeach

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            {{-- Small Explanation --}}

            <div
                class="bg-gray-50 rounded-xl
                       px-4 py-4 mt-6"
            >

                <p class="text-sm text-gray-600">

                    You don't need to enter exact times yet.
                    Choose the periods when you're generally
                    available.

                </p>

            </div>


            {{-- Navigation --}}

            <div class="flex items-center gap-3 mt-8">


                <a
                    href="{{ route('onboarding.skills') }}"

                    class="w-1/3 text-center
                           border border-gray-300
                           text-gray-700 font-semibold
                           py-3 px-4 rounded-xl
                           hover:bg-gray-100 transition"
                >
                    Back
                </a>


                <button
                    type="submit"

                    class="w-2/3 bg-gray-900
                           text-white font-semibold
                           py-3 px-4 rounded-xl
                           hover:bg-gray-800 transition"
                >
                    Finish Setup
                </button>


            </div>


        </form>


    </div>

</div>

</body>
</html>