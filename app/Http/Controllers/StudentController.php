<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\StudentProfile;
use App\Models\ClassModel;
use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    /**
     * Complete student profile after SSO login
     */
    public function completeProfile(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'murid') {
            return response()->json([
                'success' => false,
                'message' => 'Only students can complete this profile'
            ], 403);
        }

        $request->validate([
            'phone' => 'required|string|max:20|unique:users,phone,'.$user->id,
            'province_id' => 'required|exists:indonesia_provinces,id',
            'city_id' => 'required|exists:indonesia_cities,id',
            'district_id' => 'required|exists:indonesia_districts,id',
            'village_id' => 'required|exists:indonesia_villages,id',
            'school_name' => 'required|string|max:255',
            'education_level_id' => 'required|exists:education_levels,id',
            'grade' => 'required|max:255',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'password' => 'required|confirmed|string|min:8'
        ]);

        DB::beginTransaction();
        try {
            // Find or create the location
            $location = Location::firstOrCreate([
                'village_id' => $request->village_id,
            ], [
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
            ]);

            // Update user name, password, and profile status
            $user->update([
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'profile_complete' => true
            ]);

            // Create or update student profile
            $user->studentProfile()->updateOrCreate(
                ['student_id' => $user->id],
                [
                    'location_id' => $location->id,
                    'school_name' => $request->school_name,
                    'education_level_id' => $request->education_level_id,
                    'grade' => $request->grade,
                    'parent_name' => $request->parent_name,
                    'parent_phone' => $request->parent_phone
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile completed successfully',
                'data' => $user->load([
                    'studentProfile.educationLevel',
                    'studentProfile.location.province',
                    'studentProfile.location.city',
                    'studentProfile.location.district',
                    'studentProfile.location.village'
                ])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if student profile is complete
     */
    public function checkProfileStatus()
    {
        $user = auth()->user();

        if ($user->role !== 'murid') {
            return response()->json([
                'success' => false,
                'message' => 'Only students can check profile status'
            ], 403);
        }

        $hasProfile = $user->studentProfile()->exists();
        $profile = $user->studentProfile;

        return response()->json([
            'success' => true,
            'data' => [
                'profile_complete' => $user->profile_complete && $hasProfile,
                'profile' => $hasProfile ? $profile->load([
                    'educationLevel',
                    'location.province',
                    'location.city',
                    'location.district',
                    'location.village'
                ]) : null
            ]
        ]);
    }

    /**
     * Get current and upcoming classes for student
     */
    public function getCurrentAndUpcomingClasses()
    {
        $user = auth()->user();

        if ($user->role !== 'murid') {
            return response()->json([
                'success' => false,
                'message' => 'Only students can access this endpoint'
            ], 403);
        }

        $classes = $user->studentClasses()
            ->with([
                'educationLevel',
                'subject',
                'teacher',
                'location',
                'classSchedules' => function ($query) {
                    $query->where('start_time', '>=', now())
                        ->orderBy('start_time', 'asc');
                }
            ])
            ->where('status', 'active')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Current and upcoming classes retrieved successfully',
            'data' => $classes
        ]);
    }

    /**
     * Get class attendance for all members including teacher
     */
    public function getClassAttendance($classId)
    {
        $user = auth()->user();

        if ($user->role !== 'murid') {
            return response()->json([
                'success' => false,
                'message' => 'Only students can access this endpoint'
            ], 403);
        }

        // Check if student is part of this class
        $class = ClassModel::with(['students', 'teacher'])->findOrFail($classId);
        
        if (!$class->students->contains($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this class'
            ], 403);
        }

        $attendances = Attendance::where('class_id', $classId)
            ->with(['user', 'class'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Class attendance retrieved successfully',
            'data' => [
                'class' => $class,
                'attendances' => $attendances
            ]
        ]);
    }

    /**
     * Request class reschedule
     */
    public function requestReschedule(Request $request, $classId)
    {
        $user = auth()->user();

        if ($user->role !== 'murid') {
            return response()->json([
                'success' => false,
                'message' => 'Only students can request reschedule'
            ], 403);
        }

        $request->validate([
            'new_schedule' => 'required|string|max:255',
            'reason' => 'required|string|max:500',
            'type' => 'required|in:one_time,all_time'
        ]);

        // Check if student is part of this class
        $class = ClassModel::findOrFail($classId);
        
        if (!$class->students->contains($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this class'
            ], 403);
        }

        // Create reschedule request using attendance table
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'class_id' => $classId,
            'role' => 'murid',
            'note' => "Reschedule request: {$request->reason}. Type: {$request->type}. New schedule: {$request->new_schedule}",
            'reschedule_to' => $request->new_schedule
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reschedule request submitted successfully',
            'data' => $attendance
        ]);
    }

    /**
     * Get reschedule requests for student's classes
     */
    public function getRescheduleRequests()
    {
        $user = auth()->user();

        if ($user->role !== 'murid') {
            return response()->json([
                'success' => false,
                'message' => 'Only students can access this endpoint'
            ], 403);
        }

        $requests = Attendance::whereIn('class_id', $user->studentClasses()->pluck('classes.id'))
            ->whereNotNull('reschedule_to')
            ->with(['user', 'class'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Reschedule requests retrieved successfully',
            'data' => $requests
        ]);
    }
} 