<?php

namespace App\Http\Controllers;

use App\Models\TeacherLevel;
use Illuminate\Http\Request;

class TeacherLevelController extends Controller
{
    public function index()
    {
        $levels = TeacherLevel::with('teacher')->get();

        return response()->json([
            'success' => true,
            'message' => 'Teacher levels retrieved',
            'data' => $levels,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'level' => 'required|in:sd,smp,sma',
        ]);

        $level = TeacherLevel::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Teacher level created',
            'data' => $level,
        ]);
    }

    public function show($id)
    {
        $level = TeacherLevel::with('teacher')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Teacher level details',
            'data' => $level,
        ]);
    }

    public function update(Request $request, $id)
    {
        $level = TeacherLevel::findOrFail($id);

        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'level' => 'required|in:sd,smp,sma',
        ]);

        $level->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Teacher level updated',
            'data' => $level,
        ]);
    }

    public function destroy($id)
    {
        $level = TeacherLevel::findOrFail($id);
        $level->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher level deleted',
        ]);
    }
}
