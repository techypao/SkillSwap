<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Set Up Your Profile | SkillSwap</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100">

<div class="min-h-screen flex items-center justify-center px-4 py-10">

    <div class="bg-white rounded-2xl shadow-lg p-8 max-w-xl w-full">

        {{-- Step Indicator --}}
        <div class="mb-6">
            <p class="text-sm text-gray-500">
                Step 2 of 4
            </p>

            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-gray-900 h-2 rounded-full w-1/2"></div>
            </div>
        </div>


        {{-- Heading --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                Set up your profile
            </h1>

            <p class="text-gray-600 mt-2">
                Tell the SkillSwap community a little about yourself.
            </p>
        </div>


        {{-- Success Message --}}
        @if(session('success'))
            <div class="mb-6 bg-green-100 text-green-700 px-4 py-3 rounded-xl">
                {{ session('success') }}
            </div>
        @endif


        <form
            action="{{ route('onboarding.profile.store') }}"
            method="POST"
            enctype="multipart/form-data"
        >

            @csrf


            {{-- Profile Picture --}}
            <div class="mb-6">

                <label
                    for="profile_picture"
                    class="block text-sm font-semibold text-gray-700 mb-2"
                >
                    Profile Picture
                </label>

                <input
                    type="file"
                    name="profile_picture"
                    id="profile_picture"
                    accept="image/png,image/jpeg,image/webp"
                    class="block w-full text-sm text-gray-700
                           border border-gray-300 rounded-xl
                           file:border-0
                           file:bg-gray-900
                           file:text-white
                           file:px-4
                           file:py-3
                           file:mr-4
                           cursor-pointer"
                >

                <p class="text-xs text-gray-500 mt-2">
                    JPG, PNG or WEBP. Maximum file size: 2 MB.
                </p>

                @error('profile_picture')
                    <p class="text-red-500 text-sm mt-2">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- School / Organization --}}
            <div class="mb-6">

                <label
                    for="school_organization"
                    class="block text-sm font-semibold text-gray-700 mb-2"
                >
                    School / Organization
                </label>

                <input
                    type="text"
                    name="school_organization"
                    id="school_organization"
                    value="{{ old('school_organization', $user->school_organization) }}"
                    placeholder="e.g. FEU Institute of Technology"
                    class="w-full border border-gray-300 rounded-xl
                           px-4 py-3
                           focus:outline-none
                           focus:ring-2
                           focus:ring-gray-900"
                    required
                >

                @error('school_organization')
                    <p class="text-red-500 text-sm mt-2">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- Bio --}}
            <div class="mb-8">

                <label
                    for="bio"
                    class="block text-sm font-semibold text-gray-700 mb-2"
                >
                    Bio
                </label>

                <textarea
                    name="bio"
                    id="bio"
                    rows="5"
                    maxlength="500"
                    placeholder="Tell people about yourself, your interests, or what you hope to learn..."
                    class="w-full border border-gray-300 rounded-xl
                           px-4 py-3
                           focus:outline-none
                           focus:ring-2
                           focus:ring-gray-900"
                    required
                >{{ old('bio', $user->bio) }}</textarea>

                <p class="text-xs text-gray-500 mt-2">
                    Maximum of 500 characters.
                </p>

                @error('bio')
                    <p class="text-red-500 text-sm mt-2">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- Navigation --}}
            <div class="flex items-center gap-3">

                <a
                    href="{{ route('onboarding.welcome') }}"
                    class="w-1/3 text-center border border-gray-300
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