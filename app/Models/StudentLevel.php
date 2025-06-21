<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'level'
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
