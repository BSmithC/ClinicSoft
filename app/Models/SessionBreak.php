<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'start_time',
        'end_time',
        'reason',
    ];

    public function session()
    {
        return $this->belongsTo(MedicalSession::class, 'session_id');
    }
}
