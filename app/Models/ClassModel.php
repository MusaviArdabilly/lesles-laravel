<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;
    
    protected $table = 'classes';

    protected $fillable = [
        'name', 
        'level', 
        'subject', 
        'teacher_id', 
        'schedule', 
    ];
    
    // Remove this if 'schedule' is JSON and NOT a related table
    protected $casts = [
        'schedule' => 'array', // to decode JSON automatically
    ];

    // Class belongs to one teacher
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Class has many students
    public function students()
    {
        return $this->belongsToMany(User::class, 'class_students', 'class_id', 'student_id');
    }

    // Class has many attendances
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }
    // Class has many schedule
    public function schedule()
    {
        return $this->hasOne(Schedule::class);
    }
}
