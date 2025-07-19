<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|in:guru,murid'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'murid',
            'phone' => $request->phone,
        ]);

        // This uses Laravel's built-in email verification notification
        $user->sendEmailVerificationNotification();

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please verify your email.',
            'data' => compact('token'),
        ], 201);
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired link'], 403);
        }

        $user = User::findOrFail($id);

        if ($user->hasVerifiedEmail()) {
            return response()->json(['success' => true, 'message' => 'Email already verified']);
        }

        $user->markEmailAsVerified();

        return response()->json(['success' => true, 'message' => 'Email verified successfully']);
    }

    public function resendVerification(Request $request)
    {
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['success' => true, 'message' => 'Email already verified']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['success' => true, 'message' => 'Verification email resent']);
    }

    public function checkIfVerified(Request $request)
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'message' => 'Verification status retrieved',
            'data' => [
                'email_verified' => $user->hasVerifiedEmail(),
                'email_verified_at' => $user->email_verified_at,
            ],
        ]);
    }

    public function login(Request $request)
    {
        // Manual validation
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password',
            ], 401);
        }

        $user = auth()->user();

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => compact('user', 'token'),
        ]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['success' => true, 'message' => 'Logged out successfully']);
    }
}
