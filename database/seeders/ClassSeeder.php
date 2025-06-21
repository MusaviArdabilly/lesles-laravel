<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ClassModel;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = [
            [
                'level' => 'smp',
                'subject' => 'Fisika',
                'name' => 'SMP Fisika Kelas 8',
                'schedule' => [
                    'day' => 'senin',
                    'start_time' => '08:00',
                    'end_time' => '09:00'
                ]
            ],
            [
                'level' => 'sma',
                'subject' => 'Matematika',
                'name' => 'SMA Matematika Kelas 10',
                'schedule' => [
                    'day' => 'sabtu',
                    'start_time' => '14:00',
                    'end_time' => '15:00'
                ]
            ],
            [
                'level' => 'sma',
                'subject' => 'Biologi',
                'name' => 'SMA Biologi Kelas 10',
                'schedule' => [
                    'day' => 'jumat',
                    'start_time' => '16:00',
                    'end_time' => '17:00'
                ]
            ],
        ];

        foreach ($classes as $item) {
            $teacher = User::where('role', 'guru')->inRandomOrder()->first();
            $students = User::where('role', 'murid')->inRandomOrder()->limit(3)->pluck('id');

            // Fallback if no teacher or students exist
            if (!$teacher || $students->isEmpty()) {
                continue;
            }
            
            $class = ClassModel::create([
                'level' => $item['level'],
                'subject' => $item['subject'],
                'teacher_id' => $teacher->id,
                'name' => $item['name'],
                'schedule' => $item['schedule'],
            ]);

            // Attach students to the class
            $class->students()->attach($students);
        }
    }
}
