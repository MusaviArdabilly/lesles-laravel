<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\Attendance;

class DashboardController extends Controller
{
    public function index() {
        // Total guru and murid
        $totalTeacher = User::where('role', 'guru')->count();
        $totalStudent = User::where('role', 'murid')->count();

        // Total students by level
        $totalByLevel = User::where('role', 'murid')
            ->whereHas('studentLevels', function ($query) {
                $query->whereIn('level', ['sd', 'smp', 'sma']);
            })
            ->with('studentLevels')
            ->get()
            ->groupBy(function ($user) {
                return $user->studentLevels->level;
            })
            ->map(function ($group) {
                return $group->count();
            });

        //  Total class by level
        $classByLevel = ClassModel::whereIn('level', ['sd', 'smp', 'sma'])
            ->get()
            ->groupBy('level')
            ->map(fn($group) => $group->count());

        // Latest class and attendance
        $class = ClassModel::with(['teacher', 'students'])->latest()->limit(5)->get();
        $attendance = Attendance::with(['user', 'class'])->latest()->limit(5)->get();

        return response()->json([
            'message' => 'Dashboard fetched successfully',
            'data' => [
                'totalTeacher' => $totalTeacher,
                'totalStudent' => $totalStudent,
                'totalStudentByLevel' => [
                    'sd' => $totalByLevel['sd'] ?? 0,
                    'smp' => $totalByLevel['smp'] ?? 0,
                    'sma' => $totalByLevel['sma'] ?? 0,
                ],
                'totalClassByLevel' => [
                    'sd' => $classByLevel['sd'] ?? 0,
                    'smp' => $classByLevel['smp'] ?? 0,
                    'sma' => $classByLevel['sma'] ?? 0,
                ],
                'class' => $class,
                'attendance' => $attendance,
            ]
        ]);
    }
}
