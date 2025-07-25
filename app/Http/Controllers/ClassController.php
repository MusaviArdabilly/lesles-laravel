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
    // For operator
    public function getAllClasses(Request $request)
    {
        $classes = $this->applyLocationFilters(
            ClassModel::with([
                'educationLevel', 'subject', 'teacher', 'createdBy', 'members:id,name,email,phone',
                'location.province', 'location.city', 'location.district', 'location.village'
            ]),
            $request
        )
            ->orderByRaw("status = 'menunggu' DESC")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'All classes fetched successfully',
            'data' => $classes,
        ]);
    }

    // For Operator 
    // 1. Show detail of selected class
    public function getDetailClassForOperator(Request $request, $id)
    {
        $user = $request->user();

        if (!in_array($user->role, ['operator', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya operator atau admin yang dapat melakukan aksi ini.',
            ], 403);
        }

        $class = ClassModel::with([
            'educationLevel', 'subject', 'teacher', 'createdBy', 'members:id,name,email,phone',
            'location.province', 'location.city', 'location.district', 'location.village',
            'attendances.user:id,name', 'attendances' => fn($q) => $q->orderBy('attended_at', 'desc')
        ])->find($id);

        if (!$class) {
            return response()->json(['success' => false, 'message' => 'Kelas tidak ditemukan'], 404);
        }

        $class->attendances_grouped = collect($class->attendances)->groupBy(fn($a) => Carbon::parse($a->attended_at)->toDateString());
        unset($class->attendances);

        return response()->json([
            'success' => true,
            'message' => 'Detail kelas ditemukan',
            'data' => $class,
        ]);
    }

    // 2. Assign teacher or reject class
    public function assignOrReject(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['operator', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'action' => ['required', 'in:penugasan,ditolak'],
            'teacher_id' => ['required_if:action,penugasan', 'nullable', 'exists:users,id'],
        ]);

        $class = ClassModel::find($id);
        if (!$class) {
            return response()->json(['success' => false, 'message' => 'Kelas tidak ditemukan'], 404);
        }

        $class->status = $request->action === 'penugasan' ? 'aktif' : 'ditolak';
        if ($request->action === 'penugasan') {
            $class->teacher_id = $request->teacher_id;
        }
        $class->save();

        return response()->json([
            'success' => true,
            'message' => $request->action === 'penugasan' ? 'Guru berhasil ditugaskan' : 'Kelas ditolak',
            'data' => $class,
        ]);
    }

    // For Guru and Murid
    public function getAllClassesByUser(Request $request)
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');
        $status = $request->query('status');

        $query = ClassModel::where(function ($q) use ($user) {
            $q->whereHas('members', fn($q) => $q->where('users.id', $user->id))
              ->orWhere('created_by', $user->id)
              ->orWhere('teacher_id', $user->id);
        });

        if ($status) $query->where('status', $status);

        $classes = $query->with([
            'educationLevel', 'subject', 'teacher', 'createdBy', 'members:id,name,email,phone',
            'location.province', 'location.city', 'location.district', 'location.village'
        ])->latest()->get();

        if ($filter === 'today') {
            $today = Str::ucfirst(Carbon::now()->locale('id')->dayName);
            $classes = $classes->filter(fn($class) => collect($class->schedules)->contains('day', $today))->values();
        }

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
            'educationLevel', 'subject', 'teacher', 'createdBy', 'members:id,name,email,phone',
            'location.province', 'location.city', 'location.district', 'location.village',
            'attendances.user:id,name', 'attendances' => fn($q) => $q->orderBy('attended_at', 'desc')
        ])->find($id);

        if (!$class) return response()->json(['success' => false, 'message' => 'Kelas tidak ditemukan'], 404);

        $isMember = $class->members->pluck('id')->contains($user->id);
        $isCreator = $class->created_by == $user->id;
        $isTeacher = $class->teacher_id == $user->id;

        if (!$isCreator && !$isMember && !$isTeacher) {
            return response()->json(['success' => false, 'message' => 'Tidak ada akses ke kelas ini'], 403);
        }

        $class->attendances_grouped = collect($class->attendances)->groupBy(fn($a) => Carbon::parse($a->attended_at)->toDateString());
        unset($class->attendances);

        return response()->json([
            'success' => true,
            'message' => 'Detail kelas ditemukan',
            'data' => $class,
        ]);
    }

    // For Murid 
    public function store(Request $request)
    {
        $class = $this->saveClassFromRequest($request);
        return response()->json([
            'success' => true,
            'message' => 'Class created successfully',
            'data' => $class,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $class = ClassModel::find($id);
        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak ditemukan',
            ], 404);
        }
        $class = $this->saveClassFromRequest($request, $class);
        return response()->json([
            'success' => true,
            'message' => 'Class updated successfully',
            'data' => $class,
        ]);
    }

    private function saveClassFromRequest(Request $request, ClassModel $class = null)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'education_level_id' => 'required|exists:education_levels,id',
            'subject_id' => 'required|exists:subjects,id',
            'type' => 'required|in:privat,grup',
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

        // Handle location creation if any location data present
        $location = null;
        if (
            $request->filled('location_province_id') ||
            $request->filled('location_city_id') ||
            $request->filled('location_district_id') ||
            $request->filled('location_village_id') ||
            $request->filled('location_address')
        ) {
            $location = Location::create([
                'province_id' => $validated['location_province_id'] ?? null,
                'city_id' => $validated['location_city_id'] ?? null,
                'district_id' => $validated['location_district_id'] ?? null,
                'village_id' => $validated['location_village_id'] ?? null,
                'address' => $validated['location_address'] ?? null,
            ]);
        }

        // Map emails to user IDs for pivot
        $memberEmails = $validated['members'] ?? [];
        $memberIds = User::whereIn('email', $memberEmails)->pluck('id')->toArray();

        if (!$class) {
            // Create new class
            $class = ClassModel::create([
                'name' => $validated['name'],
                'education_level_id' => $validated['education_level_id'],
                'subject_id' => $validated['subject_id'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'note' => $validated['note'] ?? null,
                'location_id' => $location?->id,
                'schedules' => $validated['schedule'],
                'status' => 'menunggu',
                'created_by' => $request->user()->id,
            ]);
        } else {
            // Update existing class
            $class->update([
                'name' => $validated['name'],
                'education_level_id' => $validated['education_level_id'],
                'subject_id' => $validated['subject_id'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'note' => $validated['note'] ?? null,
                'location_id' => $location?->id ?? $class->location_id,
                'schedules' => $validated['schedule'],
            ]);
        }

        $class->members()->sync($memberIds);

        return $class->load([
            'educationLevel',
            'subject',
            'teacher',
            'location.province',
            'location.city',
            'location.district',
            'location.village',
            'members:id,name,email,phone',
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
