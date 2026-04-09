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
        Schema::table('rounds', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('name');
            $table->index(['event_id', 'is_active']);
        });

        DB::table('rounds')
            ->select('event_id')
            ->distinct()
            ->pluck('event_id')
            ->each(function (int $eventId): void {
                $activeRoundId = DB::table('rounds')
                    ->where('event_id', $eventId)
                    ->orderByDesc('number')
                    ->orderByDesc('id')
                    ->value('id');

                if ($activeRoundId) {
                    DB::table('rounds')->where('id', $activeRoundId)->update(['is_active' => true]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'is_active']);
            $table->dropColumn('is_active');
        });
    }
};
