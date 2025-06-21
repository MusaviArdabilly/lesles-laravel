<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('verification.verify');

Route::post('/auth/google/callback', [GoogleController::class, 'handleGoogleLogin']);

Route::middleware('auth:api')->group(function () { 
    Route::get('/email/check-verified', [AuthController::class, 'checkIfVerified']);
    Route::post('/email/resend', [AuthController::class, 'resendVerification']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', [UserController::class, 'me']);
    Route::put('/me', [UserController::class, 'update']);
    
    Route::middleware('verified.jwt')->group(function () {
    
        Route::get('/dashboard', [DashboardController::class, 'index']);
    
        
        Route::get('/teachers', [UserController::class, 'teachers']);
        Route::get('/students', [UserController::class, 'students']);
    
        Route::get('/class/all', [ClassController::class, 'getAllClasses']);
        Route::get('/class', [ClassController::class, 'getClassByUser']);
        Route::get('/class/upcoming', [ClassController::class, 'getUpcomingClasses']);
        Route::post('/class', [ClassController::class, 'store']);
    
    
        Route::post('/attendance', [AttendanceController::class, 'store']);
        Route::get('/attendance', [AttendanceController::class, 'getAttendanceByUser']);
        Route::get('/attendance/all', [AttendanceController::class, 'index']);
    
        Route::get('/schedule', [ScheduleController::class, 'index']);
    });

});


///////////////////////////////////////////////////////////////////////////////////////

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'php_version' => phpversion(),
        'laravel_version' => app()->version(),
    ]);
});

Route::get('/clear-cache', function () {
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');

    return 'Caches cleared!';
});