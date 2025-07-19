<?php

namespace App\Http\Controllers;

use App\Models\StudentLevel;
use Illuminate\Http\Request;

class StudentLevelController extends Controller
{
    public function index()
    {
        $levels = StudentLevel::with('student')->get();

        return response()->json([
            'success' => true,
            'message' => 'Student levels retrieved',
            'data' => $levels,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'level' => 'required|in:sd,smp,sma',
        ]);

        $level = StudentLevel::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Student level created',
            'data' => $level,
        ]);
    }

    public function show($id)
    {
        $level = StudentLevel::with('student')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Student level details',
            'data' => $level,
        ]);
    }

    public function update(Request $request, $id)
    {
        $level = StudentLevel::findOrFail($id);

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'level' => 'required|in:sd,smp,sma',
        ]);

        $level->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Student level updated',
            'data' => $level,
        ]);
    }

    public function destroy($id)
    {
        $level = StudentLevel::findOrFail($id);
        $level->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student level deleted',
        ]);
    }
}
