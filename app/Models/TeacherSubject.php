<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherSubject extends Model
{
    use HasFactory;

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
