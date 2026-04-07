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
        Schema::table('games', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('court_number');
            $table->timestamp('finished_at')->nullable()->after('started_at');
            $table->unsignedInteger('duration_seconds')->nullable()->after('finished_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'finished_at', 'duration_seconds']);
        });
    }
};
