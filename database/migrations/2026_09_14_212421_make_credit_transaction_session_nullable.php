<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('skill_session_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('credit_transactions')->whereNull('skill_session_id')->exists()) {
            throw new RuntimeException('Cannot require sessions while sessionless credit transactions exist.');
        }

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('skill_session_id')->nullable(false)->change();
        });
    }
};
