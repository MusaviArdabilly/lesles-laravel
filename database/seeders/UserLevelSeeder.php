<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\TeacherLevel;
use App\Models\StudentLevel;

class UserLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = ['sd', 'smp', 'sma'];

        // get all users with role 'guru'
        $guruUsers = User::where('role', 'guru')->get();

        // assign each level to at least one teacher
        foreach ($levels as $index => $level) {
            if (isset($guruUsers[$index])) {
                TeacherLevel::create([
                    'teacher_id' => $guruUsers[$index]->id,
                    'level' => $level,
                ]);
            }
        }

        // assign random levels to the remaining teachers (skip first 3)
        for ($i = count($levels); $i < $guruUsers->count(); $i++) {
            TeacherLevel::create([
                'teacher_id' => $guruUsers[$i]->id,
                'level' => collect($levels)->random(),
            ]);
        }

        // get all users with role 'murid'
        $muridUsers = User::where('role', 'murid')->get();

        foreach ($muridUsers as $user) {
            StudentLevel::create([
                'student_id' => $user->id,
                'level' => collect(['sd', 'smp', 'sma'])->random(),
            ]);
        }
    }
}
