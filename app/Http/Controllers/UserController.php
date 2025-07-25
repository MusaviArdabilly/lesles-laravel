<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Location;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();
        
        // Load relationships based on user role
        if ($user->role === 'murid') {
            $user->load([
                'studentProfile.educationLevel',
                'studentProfile.location.province',
                'studentProfile.location.city',
                'studentProfile.location.district',
                'studentProfile.location.village',
            ]);
        } elseif ($user->role === 'guru') {
            $user->load([
                'teacherProfile.location.province',
                'teacherProfile.location.city',
                'teacherProfile.location.district',
                'teacherProfile.location.village',
                'educationLevels',
                'subjects.educationLevel',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Current user profile',
            'data' => compact('user'),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        // --- Profile Completion Logic for New Students ---
        // Your frontend sends a `PUT /me` request for profile completion.
        // We'll detect this scenario and handle it here.
        if ($user->role === 'murid' && !$user->profile_complete) {
            
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'phone' => 'required|string|max:20|unique:users,phone,'.$user->id,
                'province_id' => 'required|exists:indonesia_provinces,id',
                'city_id' => 'required|exists:indonesia_cities,id',
                'district_id' => 'required|exists:indonesia_districts,id',
                'village_id' => 'required|exists:indonesia_villages,id',
                'school_name' => 'required|string|max:255',
                'education_level_id' => 'required|exists:education_levels,id',
                'grade' => 'required|string|max:255',
                'parent_name' => 'nullable|string|max:255',
                'parent_phone' => 'nullable|string|max:20',
                'password' => 'required|confirmed|string|min:8',
            ]);

            DB::beginTransaction();
            try {
                // Find or create the location based on the IDs from the form
                $location = Location::firstOrCreate(
                    ['village_id' => $validated['village_id']],
                    [
                        'province_id' => $validated['province_id'],
                        'city_id' => $validated['city_id'],
                        'district_id' => $validated['district_id'],
                    ]
                );

                // Update user's name, password, and set profile as complete
                $user->update([
                    'name' => $validated['name'] ?? $user->name,
                    'phone' => $validated['phone'] ?? null,
                    'password' => Hash::make($validated['password']),
                    'profile_complete' => true,
                ]);

                // Create the student's profile with all the form data
                $user->studentProfile()->create([
                    'location_id' => $location->id,
                    'school_name' => $validated['school_name'],
                    'education_level_id' => $validated['education_level_id'],
                    'grade' => $validated['grade'],
                    'parent_name' => $validated['parent_name'] ?? null,
                    'parent_phone' => $validated['parent_phone'] ?? null,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Profile completed successfully',
                    'data' => $user->fresh()->load('studentProfile.location'),
                ]);

            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Failed to complete profile', 'error' => $e->getMessage()], 500);
            }
        }

        // --- Existing General Profile Update Logic ---
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20|unique:users,phone,'.$user->id,
            'picture' => 'nullable|string'
        ];

        // Role-specific rules
        if ($user->role === 'murid') {
            $rules = array_merge($rules, [
                'location_id' => 'nullable|exists:locations,id',
                'parent_name' => 'nullable|string|max:255',
                'parent_phone' => 'nullable|string|max:20',
                'school_name' => 'nullable|string|max:255',
                'education_level_id' => 'nullable|exists:education_levels,id',
            ]);
        }

        if ($user->role === 'guru') {
            $rules = array_merge($rules, [
                'location_id' => 'nullable|exists:locations,id',
                'phone' => 'nullable|string|max:20',
            ]);
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            $user->update([
                'name' => $validated['name'] ?? $user->name,
                'phone' => $validated['phone'] ?? null,
                'picture' => $validated['picture'] ?? null,
            ]);

            if ($user->role === 'murid' && $user->profile_complete) {
                $user->studentProfile()->updateOrCreate(
                    ['student_id' => $user->id],
                    $request->only(['location_id', 'parent_name', 'parent_phone', 'school_name', 'education_level_id', 'grade'])
                );
            }

            if ($user->role === 'guru') {
                $user->teacherProfile()->updateOrCreate(
                    ['teacher_id' => $user->id],
                    [
                        'location_id' => $validated['location_id'] ?? null,
                        'phone' => $validated['phone'] ?? null,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user->fresh()->load([
                    'studentProfile.educationLevel',
                    'studentProfile.location.province',
                    'studentProfile.location.city',
                    'studentProfile.location.district',
                    'studentProfile.location.village',
                    'teacherProfile.location.province',
                    'teacherProfile.location.city',
                    'teacherProfile.location.district',
                    'teacherProfile.location.village',
                ]),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function teachers()
    {
        $teachers = User::where('role', 'guru')
            ->with(
                'educationLevels', 
                'teacherProfile.location.province',
                'teacherProfile.location.village', 
                'teacherProfile.location.district', 
                'teacherProfile.location.city', 
                )
            ->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'List of teachers',
            'data' => $teachers,
        ]);
    }

    public function teacherDetail($id)
    {
        $teacher = User::where('role', 'guru')
            ->with([
                'educationLevels',
                'subjects',
                'location.province',
                'location.city',
                'location.district',
                'location.village',
            ])
            ->find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Guru tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail guru ditemukan',
            'data' => $teacher,
        ], 200);
    }


    public function students()
    {
        $students = User::where('role', 'murid')
            ->with([
                'studentProfile.educationLevel',
                'studentProfile.location.province',
                'studentProfile.location.city',
                'studentProfile.location.district',
                'studentProfile.location.village',
            ])
            ->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'List of students',
            'data' => $students,
        ]);
    }

    public function checkByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User does not exist.'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User exist.',
            'data' => compact('user'),
        ], 200);
    }

}
