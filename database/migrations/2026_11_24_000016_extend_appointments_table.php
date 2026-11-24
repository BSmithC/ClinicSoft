<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('session_id')->nullable()->after('patient_id')->constrained('medical_sessions')->nullOnDelete();
            $table->foreignId('specialty_id')->nullable()->after('session_id')->constrained('specialties')->nullOnDelete();
            $table->text('notes')->nullable()->after('title');
            $table->timestamp('cancelled_at')->nullable()->after('active');
            $table->foreignId('rescheduled_from_id')->nullable()->after('cancelled_at')->constrained('appointments')->nullOnDelete();
            $table->softDeletes();

            $table->index(['session_id', 'starttime'], 'appointments_session_start_index');
            $table->index(['patient_id', 'date', 'status'], 'appointments_patient_date_status_index');
            $table->unique(['session_id', 'starttime', 'active'], 'appointments_session_slot_active_unique');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE appointments
                ADD CONSTRAINT appointments_status_check
                CHECK (status IN ('pending','confirmed','waiting','in_consultation','completed','cancelled','no_show'))
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE appointments DROP CHECK appointments_status_check');
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('appointments_session_slot_active_unique');
            $table->dropIndex('appointments_session_start_index');
            $table->dropIndex('appointments_patient_date_status_index');
            $table->dropSoftDeletes();
            $table->dropColumn(['session_id', 'specialty_id', 'notes', 'cancelled_at', 'rescheduled_from_id']);
        });
    }
};
