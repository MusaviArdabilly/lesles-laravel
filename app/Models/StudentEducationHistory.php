<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEducationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 
        'education_level_id', 
        'start_year', 
        'end_year'
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class);
    }
}
