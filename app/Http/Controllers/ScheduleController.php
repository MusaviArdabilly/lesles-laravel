<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ClassModel::query();

        // If you want, filter classes user is involved with
        if ($user->role === 'murid') {
            $query->whereHas('students', fn($q) => $q->where('student_id', $user->id));
        } elseif ($user->role === 'guru') {
            $query->where('teacher_id', $user->id);
        }

        $classes = $query->get();

        // Map schedules with class info
        $schedules = $classes->map(fn($class) => [
            'class_id' => $class->id,
            'class_name' => $class->name,
            'level' => $class->level,
            'subject' => $class->subject->name ?? $class->subject, // fallback if subject relation missing
            'teacher' => $class->teacher->name ?? null,
            'schedule' => $class->schedule, // already cast to array
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schedules fetched',
            'data' => $schedules,
        ]);
    }
}
