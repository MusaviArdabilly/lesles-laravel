<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Subject;
use App\Models\EducationLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassModel>
 */
class ClassModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $educationLevel = EducationLevel::first();
        if (!$educationLevel) {
            throw new \Exception('No education levels found. Please seed the education_levels table.');
        }
        $subject = Subject::first() ?? Subject::factory()->create();
        
        return [
            'name' => fake()->words(3, true),
            'education_level_id' => $educationLevel->id,
            'subject_id' => $subject->id,
            'teacher_id' => User::factory(),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected', 'assigned']),
        ];
    }
} 