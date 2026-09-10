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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('program_id')
                ->nullable()
                ->after('school_organization')
                ->constrained()
                ->nullOnDelete();
            $table->unsignedTinyInteger('year_level')
                ->nullable()
                ->after('program_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
            $table->dropColumn('year_level');
        });
    }
};
