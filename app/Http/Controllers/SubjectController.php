<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\EducationLevel;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Display a listing of the subjects.
     */
    public function index()
    {
        $subjects = Subject::with('educationLevel')->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Subjects retrieved successfully',
            'data' => $subjects
        ]);
    }

    /**
     * Store a newly created subject in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'education_level_id' => 'required|exists:education_levels,id'
        ]);

        // Check if subject with same name and education level already exists
        $existingSubject = Subject::where('name', $validated['name'])
            ->where('education_level_id', $validated['education_level_id'])
            ->first();

        if ($existingSubject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject with this name already exists for this education level'
            ], 409);
        }

        $subject = Subject::create($validated);
        $subject->load('educationLevel');

        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully',
            'data' => $subject
        ], 201);
    }

    /**
     * Display the specified subject.
     */
    public function show($id)
    {
        $subject = Subject::with('educationLevel')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Subject retrieved successfully',
            'data' => $subject
        ]);
    }

    /**
     * Update the specified subject in storage.
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'education_level_id' => 'sometimes|required|exists:education_levels,id'
        ]);

        // Check if subject with same name and education level already exists (excluding current subject)
        if (!empty($validated)) {
            $name = $validated['name'] ?? $subject->name;
            $educationLevelId = $validated['education_level_id'] ?? $subject->education_level_id;

            $existingSubject = Subject::where('name', $name)
                ->where('education_level_id', $educationLevelId)
                ->where('id', '!=', $id)
                ->first();

            if ($existingSubject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject with this name already exists for this education level'
                ], 409);
            }
        }

        $subject->update($validated);
        $subject->load('educationLevel');

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully',
            'data' => $subject
        ]);
    }

    /**
     * Remove the specified subject from storage.
     */
    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);

        // Check if subject is being used by any teachers
        if ($subject->teachers()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete subject. It is currently assigned to teachers.'
            ], 409);
        }

        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subject deleted successfully'
        ]);
    }

    /**
     * Get subjects by education level.
     */
    public function getByEducationLevel($educationLevelId)
    {
        $educationLevel = EducationLevel::findOrFail($educationLevelId);

        $subjects = Subject::where('education_level_id', $educationLevelId)->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Subjects retrieved successfully',
            'data' => $subjects
        ]);
    }

    /**
     * Search subjects by name.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2'
        ]);

        $subjects = Subject::with('educationLevel')
            ->where('name', 'like', '%' . $validated['name'] . '%')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Subjects found successfully',
            'data' => $subjects
        ]);
    }
} 