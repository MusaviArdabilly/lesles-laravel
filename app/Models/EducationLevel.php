<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name'
    ];
    
    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function studentProfiles()
    {
        return $this->hasMany(StudentProfile::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'teacher_education_levels', 'education_level_id', 'teacher_id');
    }

}
