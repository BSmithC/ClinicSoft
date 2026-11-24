<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id'], 'activity_log_subject_index');
            $table->index('created_at', 'activity_log_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
