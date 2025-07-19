<?php

namespace App\Http\Controllers;

use App\Models\TeacherLocationAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherLocationAvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $query = TeacherLocationAvailability::with(['teacher', 'location']);

        // Filter by teacher
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        // Filter by location
        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Filter by availability status
        if ($request->has('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        $availabilities = $query->paginate(10);

        return response()->json([
            'data' => $availabilities->items(),
            'pagination' => [
                'current_page' => $availabilities->currentPage(),
                'last_page' => $availabilities->lastPage(),
                'per_page' => $availabilities->perPage(),
                'total' => $availabilities->total()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'location_id' => 'required|exists:locations,id',
            'is_available' => 'boolean',
            'time_slots' => 'nullable|array',
            'time_slots.*.start' => 'required_with:time_slots|date_format:H:i',
            'time_slots.*.end' => 'required_with:time_slots|date_format:H:i|after:time_slots.*.start',
            'notes' => 'nullable|string|max:500'
        ]);

        // Check for duplicate teacher-location combination
        $existing = TeacherLocationAvailability::where('teacher_id', $request->teacher_id)
            ->where('location_id', $request->location_id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Teacher already has availability set for this location'
            ], 422);
        }

        $availability = TeacherLocationAvailability::create($request->all());

        return response()->json([
            'message' => 'Teacher location availability created successfully',
            'data' => $availability->load(['teacher', 'location'])
        ], 201);
    }

    public function show(TeacherLocationAvailability $availability)
    {
        return response()->json([
            'data' => $availability->load(['teacher', 'location'])
        ]);
    }

    public function update(Request $request, TeacherLocationAvailability $availability)
    {
        $request->validate([
            'is_available' => 'boolean',
            'time_slots' => 'nullable|array',
            'time_slots.*.start' => 'required_with:time_slots|date_format:H:i',
            'time_slots.*.end' => 'required_with:time_slots|date_format:H:i|after:time_slots.*.start',
            'notes' => 'nullable|string|max:500'
        ]);

        $availability->update($request->all());

        return response()->json([
            'message' => 'Teacher location availability updated successfully',
            'data' => $availability->load(['teacher', 'location'])
        ]);
    }

    public function destroy(TeacherLocationAvailability $availability)
    {
        $availability->delete();

        return response()->json([
            'message' => 'Teacher location availability deleted successfully'
        ]);
    }

    public function getMyAvailabilities(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'guru') {
            return response()->json([
                'success' => false,
                'message' => 'Only teachers can access this endpoint',
            ], 403);
        }

        $availabilities = TeacherLocationAvailability::where('teacher_id', $user->id)
            ->with([
                'location.province',
                'location.city',
                'location.district',
                'location.village'
            ])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'My location availabilities fetched successfully',
            'data' => $availabilities,
        ]);
    }

    public function getAvailableTeachers(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'is_available' => 'boolean',
        ]);

        $query = TeacherLocationAvailability::where('location_id', $validated['location_id'])
            ->with([
                'teacher',
                'location.province',
                'location.city',
                'location.district',
                'location.village'
            ]);

        if (isset($validated['is_available'])) {
            $query->where('is_available', $validated['is_available']);
        }

        $availabilities = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Available teachers for location fetched successfully',
            'data' => $availabilities,
        ]);
    }
}
