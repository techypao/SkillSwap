<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Choose Your Skills | SkillSwap</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100">

<div class="min-h-screen flex items-center justify-center px-4 py-10">

    <div class="bg-white rounded-2xl shadow-lg p-8 max-w-3xl w-full">

        {{-- Progress --}}
        <div class="mb-6">

            <p class="text-sm text-gray-500">
                Step 3 of 4
            </p>

            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">

                <div
                    class="bg-gray-900 h-2 rounded-full"
                    style="width: 75%;"
                ></div>

            </div>

        </div>


        {{-- Heading --}}
        <div class="mb-8">

            <h1 class="text-3xl font-bold text-gray-900">
                Choose your skills
            </h1>

            <p class="text-gray-600 mt-2">
                Tell us what you can teach and what you want to learn.
            </p>

        </div>


        {{-- Success --}}
        @if(session('success'))

            <div
                class="mb-6 bg-green-100 text-green-700
                       px-4 py-3 rounded-xl"
            >
                {{ session('success') }}
            </div>

        @endif


        @php

            $teachingValues = array_map(
                'strval',
                old('teaching_skills', $selectedTeaching)
            );

            $learningValues = array_map(
                'strval',
                old('learning_skills', $selectedLearning)
            );

        @endphp


        <form
            action="{{ route('onboarding.skills.store') }}"
            method="POST"
        >

            @csrf


            {{-- ================================================= --}}
            {{-- TEACHING SKILLS --}}
            {{-- ================================================= --}}

            <div class="mb-10">

                <div class="mb-4">

                    <h2 class="text-xl font-bold text-gray-900">
                        What can you teach?
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        Select one or more skills.
                    </p>

                </div>


                @error('teaching_skills')

                    <div
                        class="mb-4 bg-red-100
                               text-red-700
                               px-4 py-3 rounded-xl"
                    >
                        {{ $message }}
                    </div>

                @enderror


                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                    @foreach($skills as $skill)

                        <label
                            class="flex items-center gap-3
                                   border border-gray-200
                                   rounded-xl p-4
                                   cursor-pointer
                                   hover:bg-gray-50"
                        >

                            <input
                                type="checkbox"
                                name="teaching_skills[]"
                                value="{{ $skill->id }}"
                                class="w-4 h-4"

                                @checked(
                                    in_array(
                                        (string) $skill->id,
                                        $teachingValues,
                                        true
                                    )
                                )
                            >

                            <div>

                                <span class="font-medium text-gray-800">
                                    {{ $skill->name }}
                                </span>


                                @if(!$skill->is_approved)

                                    <p class="text-xs text-gray-400">
                                        Pending approval
                                    </p>

                                @endif

                            </div>

                        </label>

                    @endforeach

                </div>


                {{-- Custom Teaching Skill --}}

                <div class="mt-5">

                    <label
                        for="new_teaching_skills"
                        class="block text-sm font-semibold
                               text-gray-700 mb-2"
                    >
                        Don't see your skill?
                    </label>

                    <input
                        type="text"
                        id="new_teaching_skills"
                        name="new_teaching_skills"
                        value="{{ old('new_teaching_skills') }}"
                        placeholder="e.g. Blender, Piano, Baking"
                        class="w-full border border-gray-300
                               rounded-xl px-4 py-3
                               focus:outline-none
                               focus:ring-2
                               focus:ring-gray-900"
                    >

                    <p class="text-xs text-gray-500 mt-2">
                        You can enter multiple skills separated by commas.
                    </p>

                </div>

            </div>



            {{-- ================================================= --}}
            {{-- LEARNING SKILLS --}}
            {{-- ================================================= --}}

            <div class="mb-10">

                <div class="mb-4">

                    <h2 class="text-xl font-bold text-gray-900">
                        What do you want to learn?
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        Select one or more skills you're interested in learning.
                    </p>

                </div>


                @error('learning_skills')

                    <div
                        class="mb-4 bg-red-100
                               text-red-700
                               px-4 py-3 rounded-xl"
                    >
                        {{ $message }}
                    </div>

                @enderror


                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                    @foreach($skills as $skill)

                        <label
                            class="flex items-center gap-3
                                   border border-gray-200
                                   rounded-xl p-4
                                   cursor-pointer
                                   hover:bg-gray-50"
                        >

                            <input
                                type="checkbox"
                                name="learning_skills[]"
                                value="{{ $skill->id }}"
                                class="w-4 h-4"

                                @checked(
                                    in_array(
                                        (string) $skill->id,
                                        $learningValues,
                                        true
                                    )
                                )
                            >

                            <div>

                                <span class="font-medium text-gray-800">
                                    {{ $skill->name }}
                                </span>


                                @if(!$skill->is_approved)

                                    <p class="text-xs text-gray-400">
                                        Pending approval
                                    </p>

                                @endif

                            </div>

                        </label>

                    @endforeach

                </div>


                {{-- Custom Learning Skill --}}

                <div class="mt-5">

                    <label
                        for="new_learning_skills"
                        class="block text-sm font-semibold
                               text-gray-700 mb-2"
                    >
                        Want to learn something not listed?
                    </label>

                    <input
                        type="text"
                        id="new_learning_skills"
                        name="new_learning_skills"
                        value="{{ old('new_learning_skills') }}"
                        placeholder="e.g. Japanese, Chess, 3D Modeling"
                        class="w-full border border-gray-300
                               rounded-xl px-4 py-3
                               focus:outline-none
                               focus:ring-2
                               focus:ring-gray-900"
                    >

                    <p class="text-xs text-gray-500 mt-2">
                        You can enter multiple skills separated by commas.
                    </p>

                </div>

            </div>



            {{-- Navigation --}}

            <div class="flex items-center gap-3">

                <a
                    href="{{ route('onboarding.profile') }}"
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
                    Save & Continue
                </button>

            </div>

        </form>

    </div>

</div>

</body>
</html>