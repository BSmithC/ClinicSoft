<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'from_status',
        'to_status',
        'from_date',
        'to_date',
        'from_start',
        'to_start',
        'from_end',
        'to_end',
        'actor_id',
        'reason',
    ];

    protected $casts = [
        'from_date' => 'date:Y-m-d',
        'to_date' => 'date:Y-m-d',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
