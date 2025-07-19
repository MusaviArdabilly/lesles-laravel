<?php

namespace App\Http\Controllers;

use App\Models\EducationLevel;
use Illuminate\Http\Request;

class EducationLevelController extends Controller
{
    public function index()
    {
        $levels = EducationLevel::pluck('code', 'id');

        return response()->json([
            'success' => true,
            'message' => 'Education levels retrieved',
            'data' => $levels,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:education_levels,name',
            'description' => 'nullable|string',
        ]);

        $level = EducationLevel::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Education level created',
            'data' => $level,
        ]);
    }

    public function show($id)
    {
        $level = EducationLevel::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Education level details',
            'data' => $level,
        ]);
    }

    public function update(Request $request, $id)
    {
        $level = EducationLevel::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:education_levels,name,' . $id,
            'description' => 'nullable|string',
        ]);

        $level->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Education level updated',
            'data' => $level,
        ]);
    }

    public function destroy($id)
    {
        $level = EducationLevel::findOrFail($id);
        $level->delete();

        return response()->json([
            'success' => true,
            'message' => 'Education level deleted',
        ]);
    }
}
