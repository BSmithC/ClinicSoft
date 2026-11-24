<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
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
        'ocupation',
        'place_of_birth',
        'insurance',
        'status',
        'email',
        'password',
    ];
    public function Bill()
    {
        return $this->hasMany(Bill::class, 'users_id', 'id');
    }
    /**
     * Clinical profile (1:1). doctor_id in clinical tables remains this user id.
     */
    public function doctorProfile()
    {
        return $this->hasOne(Doctor::class);
    }

    public function medicalSessions()
    {
        return $this->hasMany(MedicalSession::class, 'doctor_id', 'id');
    }

    public function appointmentsAsDoctor()
    {
        return $this->hasMany(Appointment::class, 'doctor_id', 'id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isDoctor(): bool
    {
        return $this->hasRole('doctor');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
