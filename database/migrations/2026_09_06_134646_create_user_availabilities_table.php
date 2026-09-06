<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_availabilities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('day', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            ]);

            $table->enum('time_period', [
                'morning',
                'afternoon',
                'evening',
            ]);

            $table->timestamps();

            $table->unique([
                'user_id',
                'day',
                'time_period',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_availabilities');
    }
};