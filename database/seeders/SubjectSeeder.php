<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            // TK
            ['name' => 'Mengenal Huruf', 'education_level_id' => 1],
            ['name' => 'Mengenal Angka', 'education_level_id' => 1],
            ['name' => 'Kesenian', 'education_level_id' => 1],
            ['name' => 'Bahasa Indonesia Dasar', 'education_level_id' => 1],

            // SD
            ['name' => 'Matematika', 'education_level_id' => 2],
            ['name' => 'Bahasa Indonesia', 'education_level_id' => 2],
            ['name' => 'IPA', 'education_level_id' => 2],
            ['name' => 'IPS', 'education_level_id' => 2],
            ['name' => 'Pendidikan Agama', 'education_level_id' => 2],
            ['name' => 'PPKn', 'education_level_id' => 2],
            ['name' => 'Seni Budaya dan Prakarya', 'education_level_id' => 2],
            ['name' => 'Penjaskes', 'education_level_id' => 2],
            ['name' => 'Bahasa Inggris', 'education_level_id' => 2],

            // SMP
            ['name' => 'Matematika', 'education_level_id' => 3],
            ['name' => 'Bahasa Indonesia', 'education_level_id' => 3],
            ['name' => 'Bahasa Inggris', 'education_level_id' => 3],
            ['name' => 'IPA', 'education_level_id' => 3],
            ['name' => 'IPS', 'education_level_id' => 3],
            ['name' => 'PPKn', 'education_level_id' => 3],
            ['name' => 'Seni Budaya', 'education_level_id' => 3],
            ['name' => 'Penjaskes', 'education_level_id' => 3],
            ['name' => 'Prakarya', 'education_level_id' => 3],
            ['name' => 'Pendidikan Agama', 'education_level_id' => 3],

            // SMA
            ['name' => 'Matematika Wajib', 'education_level_id' => 4],
            ['name' => 'Matematika Peminatan', 'education_level_id' => 4],
            ['name' => 'Bahasa Indonesia', 'education_level_id' => 4],
            ['name' => 'Bahasa Inggris', 'education_level_id' => 4],
            ['name' => 'Fisika', 'education_level_id' => 4],
            ['name' => 'Kimia', 'education_level_id' => 4],
            ['name' => 'Biologi', 'education_level_id' => 4],
            ['name' => 'Ekonomi', 'education_level_id' => 4],
            ['name' => 'Geografi', 'education_level_id' => 4],
            ['name' => 'Sosiologi', 'education_level_id' => 4],
            ['name' => 'Sejarah Indonesia', 'education_level_id' => 4],
            ['name' => 'PPKn', 'education_level_id' => 4],
            ['name' => 'Seni Budaya', 'education_level_id' => 4],
            ['name' => 'Penjaskes', 'education_level_id' => 4],

            // Umum
            ['name' => 'Bahasa Inggris Umum', 'education_level_id' => 5],
            ['name' => 'Bahasa Jepang', 'education_level_id' => 5],
            ['name' => 'Bahasa Korea', 'education_level_id' => 5],
            ['name' => 'Bahasa Arab', 'education_level_id' => 5],
            ['name' => 'Public Speaking', 'education_level_id' => 5],
            ['name' => 'Basic Math', 'education_level_id' => 5],
            ['name' => 'Komputer Dasar', 'education_level_id' => 5],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }
    }
}
