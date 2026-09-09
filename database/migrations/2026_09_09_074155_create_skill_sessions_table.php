<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('skill_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('swap_request_id')
                ->unique()
                ->constrained('swap_requests')
                ->cascadeOnDelete();
            $table->foreignId('scheduled_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->enum('meeting_type', ['online', 'in_person']);
            $table->text('meeting_details')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])
                ->default('scheduled');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_sessions');
    }
};
