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
        'google_id',
        'profile_complete',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'google_id',
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

    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class, 'student_id');
    }

    public function teacherProfile()
    {
        return $this->hasOne(TeacherProfile::class, 'teacher_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function classesTaught()
    {
        return $this->hasMany(ClassModel::class, 'teacher_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects', 'teacher_id', 'subject_id');
    }

    public function educationLevels()
    {
        return $this->belongsToMany(EducationLevel::class, 'teacher_education_levels', 'teacher_id', 'education_level_id');
    }

    public function qualifications()
    {
        return $this->hasMany(TeacherQualification::class, 'teacher_id');
    }

    public function educationLevelQualifications()
    {
        return $this->hasMany(TeacherQualification::class, 'teacher_id')->where('type', 'education_level');
    }

    public function subjectQualifications()
    {
        return $this->hasMany(TeacherQualification::class, 'teacher_id')->where('type', 'subject');
    }

    public function teacherLocationAvailabilities()
    {
        return $this->hasMany(TeacherLocationAvailability::class, 'teacher_id');
    }

    public function studentClasses()
    {
        return $this->belongsToMany(ClassModel::class, 'class_students', 'student_id', 'class_id');
    }

}

