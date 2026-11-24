<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'DNI',
        'name',
        'sur_name',
        'last_name',
        'second_sur_name',
        'date_of_birth',
        'phone_number',
        'nacionality',
        'address',
        'gender',
        'sex',
        'marital_status',
        'ocupation',
        'place_of_birth',
        'blood_type',
        'insurance',
        'status',
        'email',
    ];

    protected $casts = [
        'date_of_birth' => 'date:Y-m-d',
        'status' => 'boolean',
    ];

    public function Bill()
    {
        return $this->hasMany(Bill::class, 'patient_id', 'id');
    }

    public function Appointment()
    {
        return $this->hasMany(Appointment::class, 'patient_id', 'id');
    }

    /**
     * The real column in bill_details is patients_id.
     */
    public function BillDetails()
    {
        return $this->hasMany(BillDetail::class, 'patients_id', 'id');
    }

    public function medicalHistories()
    {
        return $this->hasMany(MedicalHistory::class, 'patient_id', 'id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Full appointment history is kept even when the patient is deactivated.
     */
    public function appointmentHistory()
    {
        return $this->hasManyThrough(
            AppointmentHistory::class,
            Appointment::class,
            'patient_id',
            'appointment_id'
        )->orderBy('appointment_histories.created_at');
    }
}
