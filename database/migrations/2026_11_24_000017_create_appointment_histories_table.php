<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->restrictOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->time('from_start')->nullable();
            $table->time('to_start')->nullable();
            $table->time('from_end')->nullable();
            $table->time('to_end')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('appointment_id', 'appointment_histories_appointment_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_histories');
    }
};
