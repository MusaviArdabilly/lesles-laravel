<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ClassModel;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $cityId = null;

        // Filter city if user is operator
        if ($user->role === 'operator' && $user->location_id) {
            $cityId = optional($user->location)->city_id;
        }

        // Base queries
        $teacherQuery = User::where('role', 'guru');
        $studentQuery = User::where('role', 'murid');
        $classQuery = ClassModel::query();

        if ($cityId) {
            $teacherQuery->whereHas('teacherProfile.location', fn ($q) => $q->where('city_id', $cityId));
            $studentQuery->whereHas('studentProfile.location', fn ($q) => $q->where('city_id', $cityId));
            $classQuery->whereHas('location', fn ($q) => $q->where('city_id', $cityId));
        }

        // Current totals
        $totalTeachers = $teacherQuery->count();
        $totalStudents = $studentQuery->count();
        $totalOperators = User::where('role', 'operator')->count();
        $totalClasses = $classQuery->count();

        // Growth calculation: total count now vs total count at end of last month
        $endOfLastMonth = now()->subMonth()->endOfMonth();
        
        $teachersEndLastMonth = (clone $teacherQuery)->where('created_at', '<=', $endOfLastMonth)->count();
        $studentsEndLastMonth = (clone $studentQuery)->where('created_at', '<=', $endOfLastMonth)->count();
        $classesEndLastMonth = (clone $classQuery)->where('created_at', '<=', $endOfLastMonth)->count();

        // Class status breakdown
        $statusKelas = (clone $classQuery)->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'success' => true,
            'message' => 'Dashboard summary',
            'data' => [
                'total_teachers' => $totalTeachers,
                'teachers_growth' => $this->calcGrowth($totalTeachers, $teachersEndLastMonth),
                'total_students' => $totalStudents,
                'students_growth' => $this->calcGrowth($totalStudents, $studentsEndLastMonth),
                'total_operators' => $totalOperators,
                'total_classes' => $totalClasses,
                'classes_growth' => $this->calcGrowth($totalClasses, $classesEndLastMonth),
                'class_status' => [
                    'menunggu' => $statusKelas['menunggu'] ?? 0,
                    'aktif' => $statusKelas['aktif'] ?? 0,
                    'ditolak' => $statusKelas['ditolak'] ?? 0,
                ],
            ],
        ]);
    }

    private function calcGrowth($current, $previous): string
    {
        $difference = $current - $previous;
        
        if ($previous == 0 && $current == 0) {
            return '0% (0) dari bulan kemarin';
        }
        
        if ($previous == 0 && $current > 0) {
            return "Baru ditambahkan bulan ini (+{$current})";
        }
        
        if ($previous > 0 && $current == 0) {
            return "-100% (-{$previous}) dari bulan kemarin";
        }
        
        $percent = round((($current - $previous) / $previous) * 100);
        $sign = $difference >= 0 ? '+' : '';
        
        return "{$sign}{$percent}% ({$sign}{$difference}) dari bulan kemarin";
    }
}