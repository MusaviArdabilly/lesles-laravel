<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EducationLevel;
use App\Models\Subject;
use App\Models\TeacherQualification;
use Illuminate\Http\Request;

class TeacherQualificationController extends Controller
{
    /**
     * Get all teacher qualifications.
     */
    public function index()
    {
        $qualifications = TeacherQualification::with(['teacher', 'qualification'])->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Teacher qualifications retrieved successfully',
            'data' => $qualifications
        ]);
    }

    /**
     * Get qualifications for a specific teacher.
     */
    public function getTeacherQualifications($teacherId)
    {
        $teacher = User::where('role', 'teacher')->findOrFail($teacherId);
        
        $educationLevelQualifications = $teacher->educationLevelQualifications()->with('qualification')->get();
        $subjectQualifications = $teacher->subjectQualifications()->with('qualification')->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Teacher qualifications retrieved successfully',
            'data' => [
                'education_levels' => $educationLevelQualifications->pluck('qualification'),
                'subjects' => $subjectQualifications->pluck('qualification')
            ]
        ]);
    }

    /**
     * Get teachers qualified for a specific education level.
     */
    public function getEducationLevelTeachers($educationLevelId)
    {
        $educationLevel = EducationLevel::findOrFail($educationLevelId);
        
        $qualifications = TeacherQualification::where('type', 'education_level')
            ->where('qualification_id', $educationLevelId)
            ->with('teacher')
            ->get();
        
        $teachers = $qualifications->pluck('teacher');
        
        return response()->json([
            'success' => true,
            'message' => 'Teachers qualified for education level retrieved successfully',
            'data' => $teachers
        ]);
    }

    /**
     * Get teachers qualified for a specific subject.
     */
    public function getSubjectTeachers($subjectId)
    {
        $subject = Subject::findOrFail($subjectId);
        
        $qualifications = TeacherQualification::where('type', 'subject')
            ->where('qualification_id', $subjectId)
            ->with('teacher')
            ->get();
        
        $teachers = $qualifications->pluck('teacher');
        
        return response()->json([
            'success' => true,
            'message' => 'Teachers qualified for subject retrieved successfully',
            'data' => $teachers
        ]);
    }

    /**
     * Assign education level qualifications to a teacher.
     */
    public function assignEducationLevels(Request $request, $teacherId)
    {
        $teacher = User::where('role', 'teacher')->findOrFail($teacherId);
        
        $validated = $request->validate([
            'education_level_ids' => 'required|array',
            'education_level_ids.*' => 'exists:education_levels,id'
        ]);

        // Remove existing education level qualifications
        $teacher->educationLevelQualifications()->delete();

        // Add new education level qualifications
        $qualifications = [];
        foreach ($validated['education_level_ids'] as $educationLevelId) {
            $qualifications[] = [
                'teacher_id' => $teacherId,
                'type' => 'education_level',
                'qualification_id' => $educationLevelId,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        TeacherQualification::insert($qualifications);
        
        $teacher->load('educationLevelQualifications.qualification');
        
        return response()->json([
            'success' => true,
            'message' => 'Education level qualifications assigned successfully',
            'data' => $teacher->educationLevelQualifications->pluck('qualification')
        ]);
    }

    /**
     * Assign subject qualifications to a teacher.
     */
    public function assignSubjects(Request $request, $teacherId)
    {
        $teacher = User::where('role', 'teacher')->findOrFail($teacherId);
        
        $validated = $request->validate([
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id'
        ]);

        // Remove existing subject qualifications
        $teacher->subjectQualifications()->delete();

        // Add new subject qualifications
        $qualifications = [];
        foreach ($validated['subject_ids'] as $subjectId) {
            $qualifications[] = [
                'teacher_id' => $teacherId,
                'type' => 'subject',
                'qualification_id' => $subjectId,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        TeacherQualification::insert($qualifications);
        
        $teacher->load('subjectQualifications.qualification');
        
        return response()->json([
            'success' => true,
            'message' => 'Subject qualifications assigned successfully',
            'data' => $teacher->subjectQualifications->pluck('qualification')
        ]);
    }

    /**
     * Remove a specific qualification from a teacher.
     */
    public function removeQualification($teacherId, $type, $qualificationId)
    {
        $teacher = User::where('role', 'teacher')->findOrFail($teacherId);
        
        $qualification = TeacherQualification::where('teacher_id', $teacherId)
            ->where('type', $type)
            ->where('qualification_id', $qualificationId)
            ->firstOrFail();
        
        $qualification->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Qualification removed successfully'
        ]);
    }

    /**
     * Get my qualifications (for authenticated teacher).
     */
    public function getMyQualifications()
    {
        $teacher = auth()->user();
        
        $educationLevelQualifications = $teacher->educationLevelQualifications()->with('qualification')->get();
        $subjectQualifications = $teacher->subjectQualifications()->with('qualification')->get();
        
        return response()->json([
            'success' => true,
            'message' => 'My qualifications retrieved successfully',
            'data' => [
                'education_levels' => $educationLevelQualifications->pluck('qualification'),
                'subjects' => $subjectQualifications->pluck('qualification')
            ]
        ]);
    }
} 