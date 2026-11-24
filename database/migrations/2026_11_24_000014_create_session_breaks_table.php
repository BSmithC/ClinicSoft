<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('medical_sessions')->cascadeOnDelete();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index('session_id', 'session_breaks_session_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_breaks');
    }
};
