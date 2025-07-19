<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Location;
use App\Models\ClassModel;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ClassSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ClassController extends Controller
{
    // For Admin 
    public function getAllClasses(Request $request)
    {
        $query = ClassModel::with([
            'teacher', 
            'students', 
            'educationLevel', 
            'subject',
            'location.province',
            'location.city',
            'location.district',
            'location.village'
        ]);

        // Apply location filters
        $query = $this->applyLocationFilters($query, $request);

        $classes = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'All classes fetched successfully',
            'data' => $classes,
        ]);
    }

    public function getAllClassesByUser(Request $request)
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all'); // default to 'all'

        $classes = ClassModel::where(function ($query) use ($user) {
                $query->whereJsonContains('members_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhere('teacher_id', $user->id);
            })
            ->with([
                'educationLevel',
                'subject',
                'teacher',
                'createdBy',
                'location.province',
                'location.city',
                'location.district',
                'location.village',
            ])
            ->latest()
            ->get();

        // Filter only today’s classes if requested
        if ($filter === 'today') {
            $today = Str::ucfirst(Carbon::now()->locale('id')->dayName); // e.g. "Rabu"

            $classes = $classes->filter(function ($class) use ($today) {
                $schedules = $class->schedules ?? [];
                foreach ($schedules as $schedule) {
                    if (isset($schedule['day']) && $schedule['day'] === $today) {
                        return true;
                    }
                }
                return false;
            })->values(); // reindex
        }

        // Append member_names
        $classes->transform(function ($class) {
            $memberIds = $class->members_id ?? [];
            $memberNames = User::whereIn('id', $memberIds)->pluck('name');
            $class->member_names = $memberNames;
            return $class;
        });

        return response()->json([
            'success' => true,
            'message' => $filter === 'today' ? 'List kelas hari ini' : 'List semua kelas',
            'data' => $classes,
        ]);
    }

    public function getDetailClassByUser(Request $request, $id)
    {
        $user = $request->user();

        $class = ClassModel::with([
            'educationLevel',
            'subject',
            'teacher',
            'createdBy',
            'location.province',
            'location.city',
            'location.district',
            'location.village',
            'attendances.user' => function ($query) {
                $query->select('id', 'name');
            },
            'attendances' => function ($query) {
                $query->orderBy('attended_at', 'desc');
            },
        ])->find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak ditemukan',
            ], 404);
        }

        // Authorization: user must be creator, member, or teacher
        $isCreator = $class->created_by === $user->id;
        $isMember = is_array($class->members_id) && in_array($user->id, $class->members_id);
        $isTeacher = $class->teacher_id === $user->id;

        if (!$isCreator && !$isMember && !$isTeacher) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu tidak memiliki akses ke kelas ini',
            ], 403);
        }

        // Append member_names
        $memberIds = $class->members_id ?? [];
        $memberNames = User::whereIn('id', $memberIds)->pluck('name');
        $class->member_names = $memberNames;

        // Group attendances by date (Y-m-d)
        $attendancesGrouped = collect($class->attendances)->groupBy(function ($attendance) {
            return Carbon::parse($attendance->attended_at)->toDateString();
        });

        // Optional: replace attendances with grouped version or add new key
        $class->attendances_grouped = $attendancesGrouped;
        unset($class->attendances);

        return response()->json([
            'success' => true,
            'message' => 'Detail kelas ditemukan',
            'data' => $class,
        ]);
    }

    public function getUpcomingClasses(Request $request)
    {
        $user = $request->user();

        $nowDay = now()->dayOfWeek; // 1=Monday, 2=Tuesday, etc.
        $nowTime = now()->format('H:i');

        $classes = ClassModel::whereHas('classSchedules', function ($query) use ($nowDay, $nowTime) {
            $query->where('day', $nowDay)
                  ->where('start_time', '>=', $nowTime);
        });

        if ($user->role === 'guru') {
            $classes = $classes->where('teacher_id', $user->id);
        } elseif ($user->role === 'murid') {
            $classes = $classes->whereHas('students', fn($query) => $query->where('student_id', $user->id));
        }

        $classes = $classes->with([
            'teacher', 
            'students', 
            'educationLevel', 
            'subject', 
            'classSchedules',
            'location.province',
            'location.city',
            'location.district',
            'location.village'
        ])->get();

        return response()->json([
            'success' => true,
            'message' => 'Upcoming classes',
            'data' => $classes,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'education_level_id' => 'required|exists:education_levels,id',
            'subject_id' => 'required|exists:subjects,id',
            'type' => 'required',
            'description' => 'nullable|string',
            'note' => 'nullable|string',

            'schedule' => 'required|array|min:1',
            'schedule.*.day' => 'required|string',
            'schedule.*.start_time' => 'required|string',
            'schedule.*.end_time' => 'required|string',

            'location_province_id' => 'nullable|exists:indonesia_provinces,id',
            'location_city_id' => 'nullable|exists:indonesia_cities,id',
            'location_district_id' => 'nullable|exists:indonesia_districts,id',
            'location_village_id' => 'nullable|exists:indonesia_villages,id',
            'location_address' => 'nullable|string',

            'members' => 'nullable|array',
            'members.*' => 'email',
        ]);

        $location = null;
        if (
            $request->filled('location_province_id') ||
            $request->filled('location_city_id') ||
            $request->filled('location_district_id') ||
            $request->filled('location_village_id') ||
            $request->filled('location_address')
        ) {
            $location = Location::create([
                'province_id' => $request->input('location_province_id'),
                'city_id' => $request->input('location_city_id'),
                'district_id' => $request->input('location_district_id'),
                'village_id' => $request->input('location_village_id'),
                'address' => $request->input('location_address'),
            ]);
        }

        $memberIds = [];
        if ($request->has('members')) {
            $memberIds = User::whereIn('email', $request->input('members'))->pluck('id')->toArray();
        }

        $class = ClassModel::create([
            'name' => $validated['name'],
            'education_level_id' => $validated['education_level_id'],
            'subject_id' => $validated['subject_id'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'note' => $validated['note'] ?? null,
            'location_id' => $location?->id,
            'schedules' => $request->schedule,
            'members_id' => $memberIds,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Class created successfully',
            'data' => $class->load([
                'educationLevel',
                'subject',
                'teacher',
                'location.province',
                'location.city',
                'location.district',
                'location.village',
            ]),
        ], 201);
    }



    /**
     * Apply location filters to a query
     */
    private function applyLocationFilters($query, Request $request)
    {
        // Filter by specific location
        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Filter by administrative levels
        if ($request->has('province_id')) {
            $query->whereHas('location', function($q) use ($request) {
                $q->where('province_id', $request->province_id);
            });
        }

        if ($request->has('city_id')) {
            $query->whereHas('location', function($q) use ($request) {
                $q->where('city_id', $request->city_id);
            });
        }

        if ($request->has('district_id')) {
            $query->whereHas('location', function($q) use ($request) {
                $q->where('district_id', $request->district_id);
            });
        }

        if ($request->has('village_id')) {
            $query->whereHas('location', function($q) use ($request) {
                $q->where('village_id', $request->village_id);
            });
        }

        // Search by district name
        if ($request->has('district_name')) {
            $query->whereHas('location.district', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->district_name . '%');
            });
        }

        // Search by city name
        if ($request->has('city_name')) {
            $query->whereHas('location.city', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->city_name . '%');
            });
        }

        // Search by province name
        if ($request->has('province_name')) {
            $query->whereHas('location.province', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->province_name . '%');
            });
        }

        return $query;
    }
}
