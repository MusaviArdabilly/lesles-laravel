<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherEducationLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'education_level_id'
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class);
    }
} 