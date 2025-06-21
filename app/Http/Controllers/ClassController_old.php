<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ClassController extends Controller
{
    public function getAllClasses()
    {
        $classes = ClassModel::with(['teacher', 'students'])->get();

        return response()->json([
            'message' => 'Classes fetched successfully.',
            'classes' => $classes
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'level' => 'required|in:sd,smp,sma',
            'subject' => 'required|string',
            'teacher' => 'required|exists:users,id',
            'student' => 'required|array|min:1',
            'student.*' => 'required|exists:users,id',
            'schedule.day' => 'required|array|min:1',
            'schedule.day.*' => 'required|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'schedule.startTime' => 'required|date_format:H:i',
            'schedule.endTime' => 'required|date_format:H:i|after:schedule.startTime',
            'className' => 'required|string|max:255',
        ]);

        // Extra manual validation for student roles
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
                'teacher_id' => $data['teacher'],
                'name' => $data['className'],
                'day' => json_encode($data['schedule']['day']),
                'start_time' => $data['schedule']['startTime'],
                'end_time' => $data['schedule']['endTime'],
            ]);

            $class->students()->attach($data['student']);

            DB::commit();

            return response()->json(['message' => 'Class created successfully.', 'class' => $class], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create class.', 'details' => $e->getMessage()], 500);
        }
    }
}
