<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'location_id',
        'phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
