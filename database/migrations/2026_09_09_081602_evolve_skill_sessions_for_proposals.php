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
        Schema::table('skill_sessions', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('status');
        });

        Schema::table('skill_sessions', function (Blueprint $table) {
            $table->string('status')->default('proposed')->change();
        });

        DB::table('skill_sessions')
            ->where('status', 'scheduled')
            ->update([
                'status' => 'confirmed',
                'confirmed_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('skill_sessions')
            ->whereIn('status', ['proposed', 'confirmed'])
            ->update(['status' => 'scheduled']);

        Schema::table('skill_sessions', function (Blueprint $table) {
            $table->dropColumn('confirmed_at');
        });

        Schema::table('skill_sessions', function (Blueprint $table) {
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])
                ->default('scheduled')
                ->change();
        });
    }
};
