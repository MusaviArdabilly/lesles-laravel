<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'phone' => $this->phone,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'picture' => $this->picture,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // If user is a student, return student_level
        if ($this->role === 'murid') {
            $base['student_level'] = $this->studentLevels ? $this->studentLevels->level : null;
        }

        // If user is a teacher, return teacher_level(s)
        if ($this->role === 'guru') {
            $base['teacher_levels'] = $this->teacherLevels->pluck('level');
        }

        return $base;
    }
}
