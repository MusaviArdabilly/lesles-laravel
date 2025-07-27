<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\EducationLevelController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\TeacherLocationAvailabilityController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherQualificationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;


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

// Public routes
Route::post('/login', [AuthController::class, 'login']); //DONE
Route::post('/register', [AuthController::class, 'register']); //DONE
Route::post('/auth/google/callback', [GoogleController::class, 'handleGoogleLogin']); //DONE

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify'); //DONE

Route::get('/email/verified', function () {
    return view('auth.email-verified');
})->name('email.verified');
// Protected routes, requires auth:api
Route::middleware(['auth:api'])->group(function () {
    
    Route::get('/email/check-verified', [AuthController::class, 'checkIfVerified']); //DONE
    Route::post('/email/resend', [AuthController::class, 'resendVerification']); //DONE

    Route::post('/logout', [AuthController::class, 'logout']); //DONE

    Route::get('/me', [UserController::class, 'me']); //DONE
    Route::put('/me', [UserController::class, 'update']);

    // Routes requiring JWT verified middleware
    Route::middleware(['verified.jwt'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::get('/teachers', [UserController::class, 'teachers']); //DONE
        Route::get('/teachers/{id}', [UserController::class, 'teacherDetail']); //DONE
        Route::get('/students', [UserController::class, 'students']);
        Route::get('/students/{id}', [UserController::class, 'studentDetail']);

        // Student-specific routes
        Route::post('/student/complete-profile', [StudentController::class, 'completeProfile']); //DONE
        Route::get('/student/profile-status', [StudentController::class, 'checkProfileStatus']);
        Route::get('/student/classes/current-upcoming', [StudentController::class, 'getCurrentAndUpcomingClasses']);
        Route::get('/student/class/{id}/attendance', [StudentController::class, 'getClassAttendance']);
        Route::post('/student/class/{id}/reschedule', [StudentController::class, 'requestReschedule']);
        Route::get('/student/reschedule-requests', [StudentController::class, 'getRescheduleRequests']);

        // Teacher-specific routes
        Route::post('/teacher/complete-profile', [TeacherController::class, 'completeProfile']); //DONE
        Route::get('/teacher/profile-status', [TeacherController::class, 'checkProfileStatus']);

        Route::get('/users/check-email', [UserController::class, 'checkByEmail']); //DONE

        Route::get('/class/all', [ClassController::class, 'getAllClasses']); //DONE Operator
        Route::get('/class', [ClassController::class, 'getAllClassesByUser']); //DONE
        Route::get('/class/{id}', [ClassController::class, 'getDetailClassByUser']); //DONE
        Route::get('/class/upcoming', [ClassController::class, 'getUpcomingClasses']);
        Route::post('/class', [ClassController::class, 'store']); //DONE

        // get all class for operator, use same as user // DONE 
        Route::get('/operator/class/{id}', [ClassController::class, 'getDetailClassForOperator']); // DONE - duplikasi sama user fetch class
        Route::put('/operator/class/{id}/assign', [ClassController::class, 'assignOrReject']); // DONE
        Route::put('/operator/class/{id}/edit', [ClassController::class, 'update']); // DONE


        Route::get('/attendance/check-open', [AttendanceController::class, 'checkAttendanceOpen']); //DONE
        Route::post('/attendance', [AttendanceController::class, 'store']); //DONE
        Route::get('/attendance', [AttendanceController::class, 'getAttendanceByUser']); //fetch from class api (guru, murid)
        Route::get('/attendance/all', [AttendanceController::class, 'index']); //DONE (admin, operator) not used by class detail

        Route::get('/schedule', [ScheduleController::class, 'index']);

        // CRUD resources (master data)
        Route::apiResource('education-levels', EducationLevelController::class); //DONE
        Route::apiResource('subjects', SubjectController::class);

        // Teacher Qualifications (unified)
        Route::get('/teacher-qualifications', [TeacherQualificationController::class, 'index']);
        Route::get('/teacher-qualifications/teacher/{id}', [TeacherQualificationController::class, 'getTeacherQualifications']);
        Route::get('/teacher-qualifications/education-level/{id}', [TeacherQualificationController::class, 'getEducationLevelTeachers']);
        Route::get('/teacher-qualifications/subject/{id}', [TeacherQualificationController::class, 'getSubjectTeachers']);
        Route::post('/teacher-qualifications/teacher/{id}/education-levels', [TeacherQualificationController::class, 'assignEducationLevels']);
        Route::post('/teacher-qualifications/teacher/{id}/subjects', [TeacherQualificationController::class, 'assignSubjects']);
        Route::delete('/teacher-qualifications/teacher/{teacherId}/{type}/{qualificationId}', [TeacherQualificationController::class, 'removeQualification']);
        Route::get('/teacher-qualifications/my', [TeacherQualificationController::class, 'getMyQualifications']);

        // Additional subject routes
        Route::get('/subjects/education-level/{id}', [SubjectController::class, 'getByEducationLevel']); //DONE
        Route::get('/subjects/search', [SubjectController::class, 'search']);

        // Location
        Route::get('/locations/provinces', [LocationController::class, 'getProvinces']); //DONE
        Route::get('/locations/cities', [LocationController::class, 'getCities']); //DONE
        Route::get('/locations/districts', [LocationController::class, 'getDistricts']); //DONE
        Route::get('/locations/villages', [LocationController::class, 'getVillages']); //DONE
        Route::get('/locations/all', [LocationController::class, 'getLocations']); //DONE
        Route::get('/locations/{id}', [LocationController::class, 'getLocation']); //DONE
        Route::post('/locations', [LocationController::class, 'createLocation']); //DONE

        // Teacher Location Availability
        Route::get('/teacher-availabilities/all', [TeacherLocationAvailabilityController::class, 'index']);
        Route::get('/teacher-availabilities/my', [TeacherLocationAvailabilityController::class, 'getMyAvailabilities']);
        Route::get('/teacher-availabilities/available-teachers', [TeacherLocationAvailabilityController::class, 'getAvailableTeachers']);
        Route::get('/teacher-availabilities/{id}', [TeacherLocationAvailabilityController::class, 'show']);
        Route::post('/teacher-availabilities', [TeacherLocationAvailabilityController::class, 'store']);
        Route::put('/teacher-availabilities/{id}', [TeacherLocationAvailabilityController::class, 'update']);
        Route::delete('/teacher-availabilities/{id}', [TeacherLocationAvailabilityController::class, 'destroy']);

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