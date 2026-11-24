<?php

namespace App\Models;

use App\Enums\MedicalSessionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalSession extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'doctor_id',
        'specialty_id',
        'title',
        'date',
        'start_time',
        'end_time',
        'consultation_minutes',
        'capacity',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'status' => MedicalSessionStatus::class,
        'consultation_minutes' => 'integer',
        'capacity' => 'integer',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id', 'id');
    }

    public function doctorProfile()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'user_id');
    }

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    public function breaks()
    {
        return $this->hasMany(SessionBreak::class, 'session_id')->orderBy('start_time');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'session_id')->orderBy('starttime');
    }

    public function activeAppointments()
    {
        return $this->appointments()->active()->notCancelled();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForDoctor(Builder $query, int $doctorId): Builder
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeBookable(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(
            fn (MedicalSessionStatus $status) => $status->value,
            [MedicalSessionStatus::Scheduled, MedicalSessionStatus::Active]
        ));
    }
}
