<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Http\Resources\UserResource;
use App\Models\User;

class UserController extends Controller
{
    public function me()
    {
        // return response()->json(Auth::user());
        $user = Auth::user()->load(['studentLevels', 'teacherLevels']);
        return new UserResource($user);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|unique:users,phone,' . $user->id . '|string|max:20',
            'picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'password' => 'nullable|string|min:8|confirmed',
            'teacher_levels' => 'required_if:role,guru|array',
            'student_level' => 'required_if:role,murid|string'
        ]);

        // Update basic fields
        $user->name = $validated['name'];
        $user->phone = $validated['phone'] ?? $user->phone;

        // Handle file upload if provided
        if ($request->hasFile('picture')) {
            $path = $request->file('picture')->store('user_pictures', 'public');
            $user->picture = $path;
        }

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // Update levels based on user role
        if ($user->role === 'guru' && !empty($validated['teacher_levels'])) {
            // Delete existing teacher levels before updating
            $user->teacherLevels()->delete();

            // Add new teacher levels
            foreach ($validated['teacher_levels'] as $level) {
                $user->teacherLevels()->create(['level' => $level]); // Assuming you have a 'level' column in the TeacherLevel model
            }
        }

        if ($user->role === 'murid' && !empty($validated['student_level'])) {
            $user->student_level = $validated['student_level']; 
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => new UserResource($user),
        ]);
    }
    
    public function teachers(Request $request) {
        $level = $request->query('level');

        $teachers = User::where('role', 'guru')
            ->whereHas('teacherLevels', function ($query) use ($level) {
                if ($level) $query->where('level', $level);
            })
            ->get(['id', 'name']);

        return response()->json([
            'teachers' => $teachers
        ]);
    }
    
    public function students(Request $request) {
        $level = $request->query('level');

        $students = User::where('role', 'murid')
            ->whereHas('studentLevels', function ($query) use ($level) {
                if ($level) $query->where('level', $level);
            })
            ->get(['id', 'name']);

        return response()->json([
            'students' => $students
        ]);
    }
}
