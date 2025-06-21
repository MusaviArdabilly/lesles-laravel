<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            // Admin: get all classes
            $classes = ClassModel::with(['teacher', 'students'])
                ->get(['id', 'name', 'level', 'subject', 'schedule']);
            $userLevels = null;
        } elseif ($user->role === 'guru') {
            // Teacher: get classes where teacher_id = user.id
            $classes = ClassModel::with('students')
                ->where('teacher_id', $user->id)
                ->get(['id', 'name', 'level', 'subject', 'schedule', 'teacher_id']);
            $userLevels = $user->teacherLevels->pluck('level');
        } else {
            // Student: get classes via pivot table
            $classes = $user->classesAsStudent()
                ->with('teacher')
                ->get(['classes.id', 'name', 'level', 'subject', 'schedule', 'teacher_id']);
            $userLevels = $user->studentLevels ? $user->studentLevels->level : null;
        }

        return response()->json([
            'user' => [
                'name' => $user->name,
                'role' => $user->role,
                'level' => $userLevels
            ],
            'schedule' => $classes
        ]);
    }
}
