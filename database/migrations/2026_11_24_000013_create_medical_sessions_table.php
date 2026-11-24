<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_sessions', function (Blueprint $table) {
            $table->id();
            // doctor_id is the doctor's user id: appointments.doctor_id keeps its
            // existing meaning, so current data and FKs remain valid.
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('specialty_id')->nullable()->constrained('specialties')->nullOnDelete();
            $table->string('title');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('consultation_minutes')->default(30);
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['doctor_id', 'date', 'status'], 'medical_sessions_doctor_date_status_index');
            $table->index(['date', 'status'], 'medical_sessions_date_status_index');
            $table->index(['specialty_id', 'date'], 'medical_sessions_specialty_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_sessions');
    }
};
