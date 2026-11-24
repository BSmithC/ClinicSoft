<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'notes',
        'doctor_id',
        'patient_id',
        'drug_id',
        'session_id',
        'specialty_id',
        'status',
        'attended',
        'date',
        'starttime',
        'endtime',
        'active',
        'cancelled_at',
        'rescheduled_from_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'status' => AppointmentStatus::class,
        'attended' => 'boolean',
        'active' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id', 'id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'id');
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class, 'drug_id', 'id');
    }

    public function session()
    {
        return $this->belongsTo(MedicalSession::class, 'session_id');
    }

    public function specialty()
    {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }

    public function histories()
    {
        return $this->hasMany(AppointmentHistory::class)->orderBy('created_at');
    }

    public function rescheduledFrom()
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }

    public function rescheduledTo()
    {
        return $this->hasMany(self::class, 'rescheduled_from_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', AppointmentStatus::Cancelled->value);
    }

    /**
     * Historical appointments must not occupy a bookable slot.
     */
    public function scopeNotTerminal(Builder $query): Builder
    {
        return $query->whereNotIn('status', array_map(
            fn (AppointmentStatus $status) => $status->value,
            array_filter(AppointmentStatus::cases(), fn (AppointmentStatus $status) => $status->isTerminal())
        ));
    }

    public function scopeForDoctor(Builder $query, int $doctorId): Builder
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeOverlapping(Builder $query, string $start, string $end): Builder
    {
        return $query->where('starttime', '<', $end)
            ->where('endtime', '>', $start);
    }
}
