<?php

namespace App\Http\Controllers;

use App\Models\TeacherSubject;
use Illuminate\Http\Request;

class TeacherSubjectController extends Controller
{
    public function index()
    {
        $subjects = TeacherSubject::with('teacher')->get();

        return response()->json([
            'success' => true,
            'message' => 'Teacher subjects retrieved',
            'data' => $subjects,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
        ]);

        $subject = TeacherSubject::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Teacher subject created',
            'data' => $subject,
        ]);
    }

    public function show($id)
    {
        $subject = TeacherSubject::with('teacher')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Teacher subject details',
            'data' => $subject,
        ]);
    }

    public function update(Request $request, $id)
    {
        $subject = TeacherSubject::findOrFail($id);

        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
        ]);

        $subject->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Teacher subject updated',
            'data' => $subject,
        ]);
    }

    public function destroy($id)
    {
        $subject = TeacherSubject::findOrFail($id);
        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher subject deleted',
        ]);
    }
}
