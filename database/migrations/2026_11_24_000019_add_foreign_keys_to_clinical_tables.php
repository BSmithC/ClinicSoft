<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite cannot add foreign keys to existing tables; MySQL can.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('bills', function (Blueprint $table) {
            $table->foreign('patient_id')->references('id')->on('patients')->restrictOnDelete();
            $table->foreign('users_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('drug_id')->references('id')->on('drugs')->restrictOnDelete();
        });

        Schema::table('bill_details', function (Blueprint $table) {
            $table->foreign('bills_id')->references('id')->on('bills')->restrictOnDelete();
            $table->foreign('patients_id')->references('id')->on('patients')->restrictOnDelete();
        });

        Schema::table('medical_histories', function (Blueprint $table) {
            $table->foreign('patient_id')->references('id')->on('patients')->restrictOnDelete();
            $table->foreign('doctor_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('drug_id')->references('id')->on('drugs')->restrictOnDelete();
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->foreign('patient_id')->references('id')->on('patients')->restrictOnDelete();
            $table->foreign('doctor_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['users_id']);
            $table->dropForeign(['drug_id']);
        });

        Schema::table('bill_details', function (Blueprint $table) {
            $table->dropForeign(['bills_id']);
            $table->dropForeign(['patients_id']);
        });

        Schema::table('medical_histories', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['doctor_id']);
            $table->dropForeign(['drug_id']);
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['doctor_id']);
        });
    }
};
