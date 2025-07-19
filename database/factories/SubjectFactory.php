<?php

namespace Database\Factories;

use App\Models\EducationLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
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
        return [
            'name' => fake()->words(2, true),
            'education_level_id' => $educationLevel->id,
        ];
    }
} 