<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user || !$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email belum diverifikasi.'], 403);
        }

        return $next($request);
    }
}
