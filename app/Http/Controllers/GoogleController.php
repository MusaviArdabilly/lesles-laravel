<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class GoogleController extends Controller
{
    public function handleGoogleLogin(Request $request)
    {
        $tokenId = $request->input('id_token');

        $response = Http::get("https://oauth2.googleapis.com/tokeninfo?id_token={$tokenId}");
        $payload = $response->json();
        
        if (!isset($payload['aud']) || $payload['aud'] !== env('GOOGLE_CLIENT_ID')) {
            return response()->json(['error' => 'Invalid ID token'], 401);
        }

        // Get user info
        $email = $payload['email'];
        $name = $payload['name'] ?? '';
        $googleId = $payload['sub'];

        $user = User::where('email', $email)->first();

        if (!$user) {
            // Create new user with email_verified_at set
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'email_verified_at' => now(),
                'password' => bcrypt(Str::random(24)), // random password
                'role' => 'murid', // Default to student for SSO
                'profile_complete' => false // Flag for profile completion
            ]);
        } else {
            // Update existing user
            $user->update([
                'google_id' => $googleId,
            ]);
        }

        // Generate JWT
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Login with Google successful.',
            'data' => compact('token'),
        ], 201);
    }
}

