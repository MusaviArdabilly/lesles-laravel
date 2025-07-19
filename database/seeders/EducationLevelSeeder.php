<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EducationLevel;

class EducationLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = [
            ['code' => 'TK', 'name' => 'Taman Kanak-Kanak'],
            ['code' => 'SD', 'name' => 'Sekolah Dasar'],
            ['code' => 'SMP', 'name' => 'Sekolah Menengah Pertama'],
            ['code' => 'SMA', 'name' => 'Sekolah Menengah Atas'],
            ['code' => 'Umum', 'name' => 'Umum'],
        ];

        foreach ($levels as $level) {
            EducationLevel::create($level);
        }
    }
} 