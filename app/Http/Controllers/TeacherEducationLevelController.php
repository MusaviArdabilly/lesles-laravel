<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EducationLevel;
use App\Models\TeacherEducationLevel;
use Illuminate\Http\Request;

class TeacherEducationLevelController extends Controller
{
    /**
     * Get all teacher education level assignments.
     */
    public function index()
    {
        $assignments = TeacherEducationLevel::with(['teacher', 'educationLevel'])->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Teacher education level assignments retrieved successfully',
            'data' => $assignments
        ]);
    }

    /**
     * Get education levels for a specific teacher.
     */
    public function getTeacherEducationLevels($teacherId)
    {
        $teacher = User::where('role', 'teacher')->findOrFail($teacherId);
        $educationLevels = $teacher->educationLevels;
        
        return response()->json([
            'success' => true,
            'message' => 'Teacher education levels retrieved successfully',
            'data' => $educationLevels
        ]);
    }

    /**
     * Get teachers for a specific education level.
     */
    public function getEducationLevelTeachers($educationLevelId)
    {
        $educationLevel = EducationLevel::findOrFail($educationLevelId);
        $teachers = $educationLevel->teachers()->where('role', 'teacher')->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Education level teachers retrieved successfully',
            'data' => $teachers
        ]);
    }

    /**
     * Assign education levels to a teacher.
     */
    public function assignEducationLevels(Request $request, $teacherId)
    {
        $teacher = User::where('role', 'teacher')->findOrFail($teacherId);
        
        $validated = $request->validate([
            'education_level_ids' => 'required|array',
            'education_level_ids.*' => 'exists:education_levels,id'
        ]);

        // Sync the education levels (this will remove old assignments and add new ones)
        $teacher->educationLevels()->sync($validated['education_level_ids']);
        
        $teacher->load('educationLevels');
        
        return response()->json([
            'success' => true,
            'message' => 'Education levels assigned successfully',
            'data' => $teacher->educationLevels
        ]);
    }

    /**
     * Remove education level assignment from a teacher.
     */
    public function removeEducationLevel($teacherId, $educationLevelId)
    {
        $teacher = User::where('role', 'teacher')->findOrFail($teacherId);
        $educationLevel = EducationLevel::findOrFail($educationLevelId);
        
        $teacher->educationLevels()->detach($educationLevelId);
        
        return response()->json([
            'success' => true,
            'message' => 'Education level removed from teacher successfully'
        ]);
    }

    /**
     * Get my education levels (for authenticated teacher).
     */
    public function getMyEducationLevels()
    {
        $teacher = auth()->user();
        $educationLevels = $teacher->educationLevels;
        
        return response()->json([
            'success' => true,
            'message' => 'My education levels retrieved successfully',
            'data' => $educationLevels
        ]);
    }
} 