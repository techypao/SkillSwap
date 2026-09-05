<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    // Show the registration page
    public function create()
    {
        return view('auth.register');
    }

    // Process registration
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = new User();

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);

        // IMPORTANT:
        // Anyone registering publicly is always a normal user.
        $user->role = 'user';

        $user->save();

        // Automatically login after registration
        Auth::login($user);

        // Regenerate session for security
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}