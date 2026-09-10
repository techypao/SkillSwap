<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | SkillSwap</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100">
<div class="min-h-screen px-4 py-10">
    <div class="max-w-2xl mx-auto">

        @include('settings.partials.header', ['active' => 'profile'])

        <div class="bg-white rounded-2xl shadow-lg p-8">

            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Profile information</h1>
                <p class="text-gray-600 mt-2">Update your account details and how other students see you.</p>
            </div>

            @if (session('success'))
                <div class="mb-6 bg-green-100 text-green-700 px-4 py-3 rounded-xl">
                    {{ session('success') }}
                </div>
            @endif

            <form
                action="{{ route('settings.profile.update') }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf
                @method('PATCH')

                {{-- Name --}}
                <div class="mb-6">
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name', $user->name) }}"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3
                               focus:outline-none focus:ring-2 focus:ring-gray-900"
                        required
                    >

                    @error('name')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="mb-6">
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                        Email Address
                    </label>

                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email', $user->email) }}"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3
                               focus:outline-none focus:ring-2 focus:ring-gray-900"
                        required
                    >

                    @error('email')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Profile Picture --}}
                <div class="mb-6">
                    <label for="profile_picture" class="block text-sm font-semibold text-gray-700 mb-2">
                        Profile Picture
                    </label>

                    @if ($user->profile_picture)
                        <div class="flex items-center gap-4 mb-3">
                            <img
                                src="{{ asset('storage/'.$user->profile_picture) }}"
                                alt="{{ $user->name }}"
                                class="w-16 h-16 rounded-full object-cover border border-gray-200"
                            >

                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="remove_profile_picture"
                                    value="1"
                                    class="w-4 h-4"
                                    @checked(old('remove_profile_picture'))
                                >
                                Remove current picture
                            </label>
                        </div>
                    @endif

                    <input
                        type="file"
                        name="profile_picture"
                        id="profile_picture"
                        accept="image/png,image/jpeg,image/webp"
                        class="block w-full text-sm text-gray-700
                               border border-gray-300 rounded-xl
                               file:border-0 file:bg-gray-900 file:text-white
                               file:px-4 file:py-3 file:mr-4 cursor-pointer"
                    >

                    <p class="text-xs text-gray-500 mt-2">
                        JPG, PNG or WEBP. Maximum file size: 2 MB. Leave empty to keep your current picture.
                    </p>

                    @error('profile_picture')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- School / Organization --}}
                <div class="mb-6">
                    <label for="school_organization" class="block text-sm font-semibold text-gray-700 mb-2">
                        School / Organization
                    </label>

                    <input
                        type="text"
                        name="school_organization"
                        id="school_organization"
                        value="{{ old('school_organization', $user->school_organization) }}"
                        placeholder="e.g. FEU Institute of Technology"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3
                               focus:outline-none focus:ring-2 focus:ring-gray-900"
                        required
                    >

                    @error('school_organization')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- College Program --}}
                <div class="mb-6">
                    <label for="program_id" class="block text-sm font-semibold text-gray-700 mb-2">
                        Program
                    </label>

                    <select
                        name="program_id"
                        id="program_id"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3
                               focus:outline-none focus:ring-2 focus:ring-gray-900"
                        required
                    >
                        <option value="">Select your college program</option>

                        @foreach ($programs as $program)
                            <option
                                value="{{ $program->id }}"
                                @selected((string) old('program_id', $user->program_id) === (string) $program->id)
                            >
                                {{ $program->abbreviation ? $program->abbreviation.' — ' : '' }}{{ $program->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('program_id')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Year Level --}}
                <div class="mb-6">
                    <label for="year_level" class="block text-sm font-semibold text-gray-700 mb-2">
                        Year Level
                    </label>

                    <select
                        name="year_level"
                        id="year_level"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3
                               focus:outline-none focus:ring-2 focus:ring-gray-900"
                        required
                    >
                        <option value="">Select your year level</option>

                        @foreach ([1, 2, 3, 4, 5] as $yearLevel)
                            <option
                                value="{{ $yearLevel }}"
                                @selected((string) old('year_level', $user->year_level) === (string) $yearLevel)
                            >
                                {{ $yearLevel }}{{ $yearLevel === 1 ? 'st' : ($yearLevel === 2 ? 'nd' : ($yearLevel === 3 ? 'rd' : 'th')) }} Year
                            </option>
                        @endforeach
                    </select>

                    @error('year_level')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Bio --}}
                <div class="mb-8">
                    <label for="bio" class="block text-sm font-semibold text-gray-700 mb-2">
                        Bio
                    </label>

                    <textarea
                        name="bio"
                        id="bio"
                        rows="5"
                        maxlength="500"
                        placeholder="Tell people about yourself, your interests, or what you hope to learn..."
                        class="w-full border border-gray-300 rounded-xl px-4 py-3
                               focus:outline-none focus:ring-2 focus:ring-gray-900"
                        required
                    >{{ old('bio', $user->bio) }}</textarea>

                    <p class="text-xs text-gray-500 mt-2">Maximum of 500 characters.</p>

                    @error('bio')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="w-full bg-gray-900 text-white font-semibold py-3 px-4 rounded-xl
                           hover:bg-gray-800 transition"
                >
                    Save Changes
                </button>
            </form>

        </div>
    </div>
</div>
</body>
</html>
