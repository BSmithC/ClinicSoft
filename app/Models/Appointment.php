<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'doctor_id',
        'patient_id',
        'drug_id',
        'status',
        'attended',
        'date',
        'starttime',
        'endtime',
        'active',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'status' => AppointmentStatus::class,
        'attended' => 'boolean',
        'active' => 'boolean',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', AppointmentStatus::Cancelled->value);
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
