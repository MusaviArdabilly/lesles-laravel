<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherQualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'type',
        'qualification_id'
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class, 'qualification_id')->where('type', 'education_level');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'qualification_id')->where('type', 'subject');
    }

    public function qualification()
    {
        if ($this->type === 'education_level') {
            return $this->belongsTo(EducationLevel::class, 'qualification_id');
        } else {
            return $this->belongsTo(Subject::class, 'qualification_id');
        }
    }
} 