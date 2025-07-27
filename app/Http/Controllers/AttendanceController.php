<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function checkAttendanceOpen(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'murid') {
            return response()->json([
                'success' => false,
                'message' => 'Only student can access this endpoint.',
            ], 403);
        }

        $today = Carbon::today()->toDateString();

        // Query classes where user is a member, and guru has already done attendance today
        $availableClasses = ClassModel::whereJsonContains('members_id', $user->id)
            ->whereHas('attendances', function ($query) use ($today) {
                $query->where('role', 'guru')
                    ->whereDate('attended_at', $today);
            })
            ->with([
                'educationLevel',
                'subject',
                'teacher',
                'location.province',
                'location.city',
                'location.district',
                'location.village',
            ])
            ->get();

        if ($availableClasses->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Kelas tersedia untuk absensi hari ini.',
                'data' => $availableClasses,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Belum ada guru yang membuka absensi hari ini.',
            'data' => [],
        ]);
    }
    
    // Store attendance record
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'role' => ['required', Rule::in(['guru', 'murid'])],
            'attended_at' => 'nullable|date',
            'reschedule_from' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        // Role check
        if ($user->role !== 'admin' && $user->role !== $validated['role']) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Murid can only attend if guru attended or murid already attended today
        if ($validated['role'] === 'murid') {
            $date = $validated['attended_at'] ? date('Y-m-d', strtotime($validated['attended_at'])) : date('Y-m-d');

            $guruAttended = Attendance::where('class_id', $validated['class_id'])
                ->where('role', 'guru')
                ->whereDate('attended_at', $date)
                ->exists();

            $muridAttended = Attendance::where('class_id', $validated['class_id'])
                ->where('role', 'murid')
                ->where('user_id', $user->id)
                ->whereDate('attended_at', $date)
                ->exists();

            if (!$guruAttended && !$muridAttended) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guru belum membuka kehadiran untuk sesi ini.',
                ], 403);
            }
        }

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'class_id' => $validated['class_id'],
            'role' => $validated['role'],
            'attended_at' => now(),
            'reschedule_from' => $validated['reschedule_from'] ?? null,
            'note' => $validated['note'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => $attendance]);
    }

    // Get attendance for current authenticated user
    public function getAttendanceByUser(Request $request)
    {
        $user = $request->user();

        $attendances = Attendance::where('user_id', $user->id)
            ->with(['classModel', 'classModel.teacher'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'User attendances fetched',
            'data' => $attendances,
        ]);
    }

    // Get all attendances (for admins/operators)
    public function index(Request $request)
    {
        $user = $request->user();

        // Check if the role is allowed
        if (!in_array($user->role, ['admin', 'operator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Build the query
        $query = Attendance::with(['user', 'class'])
            ->orderBy('created_at', 'desc');

        // Optional filter by user_id
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $attendances = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Attendances fetched',
            'data' => $attendances,
        ]);
    }

}
