<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'role',
        'phone',
        'email',
        'password',
        'picture',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Teacher: has many subjects
    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class, 'teacher_id');
    }

    // Teacher: has many levels
    public function teacherLevels()
    {
        return $this->hasMany(TeacherLevel::class, 'teacher_id');
    }

    // Student: has one levels
    public function studentLevels()
    {
        return $this->hasOne(StudentLevel::class, 'student_id');
    }

    // Teacher: has many availabilities
    public function availabilities()
    {
        return $this->hasMany(TeacherAvailability::class, 'teacher_id');
    }

    // Teacher: teaches many classes
    public function classesAsTeacher()
    {
        return $this->hasMany(ClassModel::class, 'teacher_id');
    }

    // Student: belongs to many classes
    public function classesAsStudent()
    {
        return $this->belongsToMany(ClassModel::class, 'class_students', 'student_id', 'class_id');
    }

    // User: has many attendances
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

}

