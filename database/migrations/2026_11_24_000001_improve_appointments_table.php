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
            if (! Schema::hasColumn('appointments', 'status')) {
                $table->string('status')->default('pending')->after('patient_id');
            }

            if (! Schema::hasColumn('appointments', 'drug_id')) {
                $table->foreignId('drug_id')->nullable()->after('patient_id');
            }

            $table->index(['doctor_id', 'date'], 'appointments_doctor_date_index');
            $table->index('patient_id', 'appointments_patient_id_index');
            $table->index('status', 'appointments_status_index');
            $table->index('date', 'appointments_date_index');
            $table->unique(['doctor_id', 'date', 'starttime', 'active'], 'appointments_doctor_date_start_active_unique');
        });

        // SQLite cannot add foreign keys to an existing table; production MySQL can.
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreign('doctor_id')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('patient_id')->references('id')->on('patients')->restrictOnDelete();
                $table->foreign('drug_id')->references('id')->on('drugs')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropForeign(['doctor_id']);
                $table->dropForeign(['patient_id']);
                $table->dropForeign(['drug_id']);
            });
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('appointments_doctor_date_start_active_unique');
            $table->dropIndex('appointments_doctor_date_index');
            $table->dropIndex('appointments_patient_id_index');
            $table->dropIndex('appointments_status_index');
            $table->dropIndex('appointments_date_index');
            $table->dropColumn(['status', 'drug_id']);
        });
    }
};
