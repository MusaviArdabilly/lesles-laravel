<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index() {
        $attendances = Attendance::with(['user', 'class'])->get();
        return response()->json([
            'data' => $attendances
        ]);
    }

    public function getAttendanceByUser() {
        $user = Auth::user(); // Get the currently authenticated user

        $attendances = Attendance::with(['user', 'class'])
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'data' => $attendances
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $classId = $request->input('class_id');
        $class = ClassModel::findOrFail($classId);

        // Get schedule as array (no json_decode needed)
        $schedule = $class->schedule;
        if (!$schedule) {
            return response()->json(['message' => 'Jadwal tidak tersedia.'], 403);
        }

        $today = strtolower(now()->locale('id')->isoFormat('dddd')); // e.g. 'sabtu'
        $now = Carbon::now();

        if (strtolower($schedule['day']) !== $today) {
            return response()->json(['message' => 'Tidak ada kelas hari ini.'], 403);
        }

        $start = Carbon::createFromFormat('H:i', $schedule['start_time']);
        $end = Carbon::createFromFormat('H:i', $schedule['end_time']);

        if (!$now->between($start, $end)) {
            return response()->json(['message' => 'Check-in hanya diperbolehkan saat jam pelajaran'], 403);
        }

        // Prevent duplicate check-in
        $alreadyCheckedIn = Attendance::where('user_id', $user->id)
            ->where('class_id', $class->id)
            ->whereDate('created_at', $now->toDateString())
            ->exists();

        if ($alreadyCheckedIn) {
            return response()->json(['message' => 'Sudah check-in hari ini'], 400);
        }

        Attendance::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'role' => $user->role, 
            'type' => 'check_in',
        ]);

        return response()->json(['message' => 'Check-in berhasil']);
    }


    // public function store(Request $request)
    // {
    //     $user = Auth::user();
    //     $classId = $request->input('class_id');
    //     $class = ClassModel::findOrFail($classId);

    //     // Determine day of week
    //     $today = strtolower(now()->format('l')); // e.g. 'monday'
    //     $now = Carbon::now();
        
    //     $schedule = $class->schedule;
    //     if (!$schedule) {
    //         return response()->json(['message' => 'Tidak ada kelas hari ini.'], 403);
    //     }

    //     // Localized current day (in Bahasa Indonesia)
    //     $today = strtolower(now()->locale('id')->isoFormat('dddd')); // e.g., 'sabtu'
    //     $now = Carbon::now();

    //     // Compare today with the class schedule day
    //     if (strtolower($schedule['day']) !== $today) {
    //         return response()->json(['message' => 'Tidak ada kelas hari ini.'], 403);
    //     }

    //     // Parse start and end time from schedule
    //     $start = Carbon::createFromFormat('H:i', $schedule['start_time']);
    //     $end = Carbon::createFromFormat('H:i', $schedule['end_time']);

    //     if ($user->isTeacher()) {
    //         return $this->handleTeacher($user, $class, $now, $start, $end);
    //     }

    //     return $this->handleStudent($user, $class, $now, $start, $end);
    // }

    // private function handleTeacher($user, $class, $now, $start, $end)
    // {
    //     // Clock In
    //     $earlyClockIn = $start->copy()->subMinutes(15);
    //     $lateClockOut = $end->copy()->addMinutes(15);

    //     // Prevent duplicate clock-in or clock-out
    //     $existing = Attendance::where('user_id', $user->id)
    //         ->where('class_id', $class->id)
    //         ->whereDate('timestamp', now()->toDateString())
    //         ->get();

    //     $hasClockIn = $existing->where('type', 'clock_in')->isNotEmpty();
    //     $hasClockOut = $existing->where('type', 'clock_out')->isNotEmpty();

    //     if (!$hasClockIn && $now->between($earlyClockIn, $start)) {
    //         Attendance::create([
    //             'user_id' => $user->id,
    //             'class_id' => $class->id,
    //             'role' => 'guru',
    //             'type' => 'clock_in',
    //             'timestamp' => $now,
    //         ]);
    //         return response()->json(['message' => 'Clocked in successfully']);
    //     }

    //     if ($hasClockIn && !$hasClockOut && $now->greaterThanOrEqualTo($end)) {
    //         Attendance::create([
    //             'user_id' => $user->id,
    //             'class_id' => $class->id,
    //             'role' => 'guru',
    //             'type' => 'clock_out',
    //             'timestamp' => $now,
    //         ]);
    //         return response()->json(['message' => 'Clocked out successfully']);
    //     }

    //     return response()->json(['message' => 'Invalid clock-in/out time or already done'], 400);
    // }

    // private function handleStudent($user, $class, $now, $start, $end)
    // {
    //     if (!$now->between($start, $end)) {
    //         return response()->json(['message' => 'Check-in is only allowed during class time'], 403);
    //     }

    //     $alreadyCheckedIn = Attendance::where('user_id', $user->id)
    //         ->where('class_id', $class->id)
    //         ->where('type', 'check_in')
    //         ->whereDate('timestamp', now()->toDateString())
    //         ->exists();

    //     if ($alreadyCheckedIn) {
    //         return response()->json(['message' => 'Already checked in'], 400);
    //     }

    //     Attendance::create([
    //         'user_id' => $user->id,
    //         'class_id' => $class->id,
    //         'role' => 'murid',
    //         'type' => 'check_in',
    //         'timestamp' => $now,
    //     ]);

    //     return response()->json(['message' => 'Check-in successful']);
    // }
}
