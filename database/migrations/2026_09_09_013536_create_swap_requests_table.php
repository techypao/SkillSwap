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
        Schema::create('swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('recipient_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('offered_skill_id')
                ->constrained('skills')
                ->restrictOnDelete();
            $table->foreignId('requested_skill_id')
                ->constrained('skills')
                ->restrictOnDelete();
            $table->text('message')->nullable();
            $table->enum('status', [
                'pending',
                'accepted',
                'rejected',
                'cancelled',
            ])->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique([
                'sender_id',
                'recipient_id',
                'offered_skill_id',
                'requested_skill_id',
                'status',
            ], 'swap_requests_unique_exchange_status');
            $table->index(['sender_id', 'status', 'created_at']);
            $table->index(['recipient_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swap_requests');
    }
};
