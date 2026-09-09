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
        Schema::table('swap_requests', function (Blueprint $table) {
            $table->dropUnique('swap_requests_unique_exchange_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('swap_requests', function (Blueprint $table) {
            $table->unique([
                'sender_id',
                'recipient_id',
                'offered_skill_id',
                'requested_skill_id',
                'status',
            ], 'swap_requests_unique_exchange_status');
        });
    }
};
