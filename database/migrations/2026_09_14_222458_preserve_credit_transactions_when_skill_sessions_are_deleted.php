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
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropForeign(['skill_session_id']);
            $table->foreign('skill_session_id')
                ->references('id')
                ->on('skill_sessions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropForeign(['skill_session_id']);
            $table->foreign('skill_session_id')
                ->references('id')
                ->on('skill_sessions')
                ->cascadeOnDelete();
        });
    }
};
