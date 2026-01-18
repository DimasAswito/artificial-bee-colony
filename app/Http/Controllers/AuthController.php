<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showSignup()
    {
        return view('pages.auth.signup', ['title' => 'Sign Up']);
    }

    public function signup(Request $request)
    {
        // 1. Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        try {
            // 2. Create user in local database
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            return redirect()->route('signin')->with('success', 'Registration successful! Please sign in.');
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred: ' . $e->getMessage())->withInput();
        }
    }

    public function signin(Request $request)
    {
        // 1. Validate the request
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // 2. Attempt login using local database
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard')->with('success', 'Signed in successfully!');
        }

        // 3. Failed login
        return back()->with('error', 'Email atau password salah')->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('signin')->with('success', 'Logged out successfully.');
    }

    // --- Google Auth Methods ---

    public function redirectToGoogle(Request $request)
    {
        $type = $request->query('type', 'signin'); // 'signin' or 'signup'

        $supabaseUrl = env('VITE_SUPABASE_URL');
        $callbackUrl = route('auth.google.callback');

        // Construct Supabase OAuth URL
        // Reference: https://supabase.com/docs/guides/auth/social-login/auth-google
        // Note: We pass params directly for the authorize endpoint.
        $query = http_build_query([
            'provider' => 'google',
            'redirect_to' => $callbackUrl,
            'access_type' => 'offline',
            'prompt' => 'select_account consent',
        ]);

        // Store the flow type in session so we know what to do in exchangeToken
        session(['auth_flow_type' => $type]);

        return redirect("{$supabaseUrl}/auth/v1/authorize?{$query}");
    }

    public function handleGoogleCallback()
    {
        return view('pages.auth.callback');
    }

    public function exchangeToken(Request $request)
    {
        $accessToken = $request->input('access_token');

        if (!$accessToken) {
            return response()->json(['error' => 'No access token provided'], 400);
        }

        try {
            // 1. Get User Data from Supabase using the token
            $supabaseUrl = env('VITE_SUPABASE_URL');
            $supabaseKey = env('VITE_SUPABASE_ANON_KEY');

            $response = Http::withHeaders([
                'apikey' => $supabaseKey,
                'Authorization' => "Bearer {$accessToken}",
            ])->get("{$supabaseUrl}/auth/v1/user");

            if (!$response->successful()) {
                Log::error('Supabase User Fetch Error:', $response->json());
                return response()->json(['error' => 'Failed to fetch user data from provider'], 400);
            }

            $userData = $response->json();
            $email = $userData['email'] ?? null;
            $name = $userData['user_metadata']['full_name'] ?? $userData['user_metadata']['name'] ?? 'Google User';

            if (!$email) {
                return response()->json(['error' => 'Email not found in consumer profile'], 400);
            }

            // 2. Determine Flow Type
            $flowType = session('auth_flow_type', 'signin'); // Default to signin if session lost
            session()->forget('auth_flow_type'); // Clear session

            // 3. Check Local Database
            $localUser = User::where('email', $email)->first();

            if ($flowType === 'signup') {
                // Handle Sign Up
                if ($localUser) {
                    // User already exists, redirect to signin with info
                    session()->flash('success', 'You already have an account. Please sign in.');
                    return response()->json(['redirect' => route('signin')]);
                }

                // Create new user
                // We create a random password since they login with Google
                User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(str()->random(24)),
                    // 'google_id' => ... if you have a column for it
                ]);

                session()->flash('success', 'Registration successful! Please sign in with Google.');
                return response()->json(['redirect' => route('signin')]);
            } else {
                // Handle Sign In (Strict)
                if (!$localUser) {
                    // ERROR: Account not registered locally
                    // We do NOT auto-register here per user request.
                    return response()->json(['error' => 'Account not registered. Please sign up first.']);
                }

                // Login the user
                Auth::login($localUser);

                session()->flash('success', 'Signed in successfully via Google!');
                return response()->json(['redirect' => route('dashboard')]);
            }
        } catch (\Exception $e) {
            Log::error('Google Exchange Error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error processing login'], 500);
        }
    }
}
