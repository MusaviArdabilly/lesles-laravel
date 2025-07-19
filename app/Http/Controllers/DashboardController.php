<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();

        $totalTeachers = User::where('role', 'guru')->count();
        $totalStudents = User::where('role', 'murid')->count();
        $totalClasses = ClassModel::count();

        $upcomingClasses = ClassModel::whereHas('classSchedules', function ($query) use ($now) {
            $query->where('day', $now->dayOfWeek)
                  ->where('start_time', '>=', $now->format('H:i'));
        })->count();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard summary',
            'data' => [
                'total_teachers' => $totalTeachers,
                'total_students' => $totalStudents,
                'total_classes' => $totalClasses,
                'upcoming_classes_today' => $upcomingClasses,
            ],
        ]);
    }
}
