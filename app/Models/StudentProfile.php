<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'location_id',
        'address',
        'parent_name',
        'parent_phone',
        'school_name',
        'education_level_id',
        'grade',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
