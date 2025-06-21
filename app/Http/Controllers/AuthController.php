<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string|in:guru,murid',
            'phone' => 'nullable|string|max:20'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'murid',
            'phone' => $request->phone,
        ]);

        $user->sendEmailVerificationNotification($user);
        
        $user->refresh();  // reload from DB to get all fields, including email_verified_at

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    protected function sendVerificationEmail(User $user)
    {
        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        Mail::raw("Click the link to verify your email: $verifyUrl", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Verify Email Address');
        });
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string)$hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email verified successfully.']);
    }

    public function resendVerification(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $this->sendVerificationEmail($user);

        return response()->json(['message' => 'Verification email resent.']);
    }

    public function checkIfVerified(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        return response()->json([
            'email_verified' => (bool) $user->hasVerifiedEmail(),
            'email_verified_at' => $user->email_verified_at,
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Email atau password salah'], 401);
        }

        $user = JWTAuth::user();

        return response()->json([
            'user' => [
                'name' => $user->name,
            ],
            'token' => $token,
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Berhasil keluar']);
    }
}
