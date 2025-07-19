<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Location;
use App\Models\TeacherProfile;
use App\Models\TeacherEducationLevel;
use App\Models\TeacherSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    /**
     * Complete teacher profile after registration/email verification
     */
    public function completeProfile(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'guru') {
            return response()->json([
                'success' => false,
                'message' => 'Only teachers can complete this profile'
            ], 403);
        }

        $request->validate([
            'province_id' => 'required|exists:indonesia_provinces,id',
            'city_id' => 'required|exists:indonesia_cities,id',
            'district_id' => 'required|exists:indonesia_districts,id',
            'village_id' => 'required|exists:indonesia_villages,id',
            'education_level_ids' => 'required|array|min:1',
            'education_level_ids.*' => 'exists:education_levels,id',
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        DB::beginTransaction();

        try {
            // Find or create the location based on the IDs from the form
            $location = Location::firstOrCreate(
                ['village_id' => $request->village_id],
                [
                    'province_id' => $request->province_id,
                    'city_id' => $request->city_id,
                    'district_id' => $request->district_id,
                ]
            );

            // Update user's phone and set profile as complete
            $user->update([
                'profile_complete' => true,
            ]);

            // Create or update the teacher's profile with location
            $user->teacherProfile()->updateOrCreate(
                ['teacher_id' => $user->id],
                [
                    'location_id' => $location->id,
                ]
            );

            // Sync education levels (this will handle both create and delete)
            $user->educationLevels()->sync($request->education_level_ids);

            // Sync subjects (this will handle both create and delete)
            $user->subjects()->sync($request->subject_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Teacher profile completed successfully',
                'data' => $user->fresh()->load([
                    'teacherProfile.location.province',
                    'teacherProfile.location.city',
                    'teacherProfile.location.district',
                    'teacherProfile.location.village',
                    'educationLevels',
                    'subjects.educationLevel'
                ]),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false, 
                'message' => 'Failed to complete teacher profile', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if teacher profile is complete
     */
    public function checkProfileStatus(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'guru') {
            return response()->json([
                'success' => false,
                'message' => 'Only teachers can check this status'
            ], 403);
        }

        $isComplete = $user->profile_complete && 
                     $user->teacherProfile && 
                     $user->educationLevels()->count() > 0 && 
                     $user->subjects()->count() > 0;

        return response()->json([
            'success' => true,
            'data' => [
                'is_complete' => $isComplete,
                'profile_complete' => $user->profile_complete,
                'has_location' => $user->teacherProfile && $user->teacherProfile->location_id,
                'has_education_levels' => $user->educationLevels()->count() > 0,
                'has_subjects' => $user->subjects()->count() > 0,
            ]
        ]);
    }
} 