<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherLocationAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'location_id',
        'is_available',
        'time_slots',
        'notes'
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'time_slots' => 'array'
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get formatted time slots for display
     */
    public function getFormattedTimeSlotsAttribute()
    {
        if (!$this->time_slots) {
            return null;
        }

        return collect($this->time_slots)->map(function ($slot) {
            return $slot['start'] . ' - ' . $slot['end'];
        })->join(', ');
    }
} 