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
        Schema::table('skill_sessions', function (Blueprint $table) {
            $table->timestamp('sender_confirmed_at')->nullable()->after('confirmed_at');
            $table->timestamp('recipient_confirmed_at')->nullable()->after('sender_confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skill_sessions', function (Blueprint $table) {
            $table->dropColumn(['sender_confirmed_at', 'recipient_confirmed_at']);
        });
    }
};
