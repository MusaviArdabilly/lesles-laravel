<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use App\Models\ClassModel;
use App\Models\Schedule;
use App\Models\User;

class ClassController extends Controller
{
    public function getAllClasses()
    {
        $classes = ClassModel::latest()->with(['teacher', 'students'])->get();

        return response()->json([
            'message' => 'Classes fetched successfully.',
            'data' => $classes
        ], 200);
    }

    public function getClassByUser()
    {
        $user = Auth::user();

        if ($user->role === 'guru') {
            $classes = ClassModel::with(['teacher', 'students'])
                ->where('teacher_id', $user->id)
                ->get();
        } elseif ($user->role === 'murid') {
            $classes = ClassModel::with(['teacher', 'students'])
                ->whereHas('students', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                })
                ->get();
        } else {
            return response()->json([
                'message' => 'Unauthorized role',
                'data' => []
            ], 403);
        }

        return response()->json([
            'message' => 'Classes fetched successfully.',
            'data' => $classes
        ]);
    }
    
    public function getUpcomingClasses()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            // Admin: get all classes
            $classes = ClassModel::with(['teacher', 'students'])
                ->get(['id', 'name', 'level', 'subject', 'schedule', 'teacher_id']);
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

        // Attach `has_attended` flag
        $classes->each(function ($cls) use ($user) {
            $hasAttended = DB::table('attendances')
                ->where('user_id', $user->id)
                ->where('class_id', $cls->id)
                ->exists();

            $cls->has_attended = $hasAttended;
        });

        return response()->json([
            'data' => $classes
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'level' => 'required|in:sd,smp,sma',
            'subject' => 'required|string',
            'teacherId' => 'required|exists:users,id',
            'studentId' => 'required|array|min:1',
            'studentId.*' => 'required|exists:users,id',

            'schedule' => 'required|array|min:1',
            'schedule.day' => 'required|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'schedule.start_time' => 'required|date_format:H:i',
            'schedule.end_time' => 'required|date_format:H:i|after:schedule.*.start_time',
            // changed to one class one time
            // 'schedule.*.day' => 'required|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            // 'schedule.*.start_time' => 'required|date_format:H:i',
            // 'schedule.*.end_time' => 'required|date_format:H:i|after:schedule.*.start_time',

            'name' => 'required|string|max:255',
        ]);

        // Validate that all students have 'murid' role
        $validator->after(function ($validator) use ($request) {
            if ($request->has('student')) {
                $invalid = User::whereIn('id', $request->student)
                    ->where('role', '!=', 'murid')
                    ->pluck('id')
                    ->toArray();

                if (!empty($invalid)) {
                    $validator->errors()->add(
                        'student',
                        'These users are not students (murid): ' . implode(', ', $invalid)
                    );
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        DB::beginTransaction();
        try {
            $class = ClassModel::create([
                'level' => $data['level'],
                'subject' => $data['subject'],
                'teacher_id' => $data['teacherId'],
                'name' => $data['name'],
                'schedule' => $data['schedule'],
            ]);

            $class->students()->attach($data['studentId']);

            DB::commit();

            return response()->json([
                'message' => 'Class created successfully.',
                'class' => $class
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create class.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
