<?php

use App\Enums\GameLogSide;
use App\Enums\GameLogType;
use App\Models\Game;
use App\Models\User;
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
        Schema::create('game_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Game::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'player_one_id')
                ->constrained('users');
            $table->foreignIdFor(User::class, 'player_two_id')
                ->constrained('users');
            $table->unsignedInteger('sequence');
            $table->enum('type', GameLogType::values());
            $table->enum('side', GameLogSide::values());
            $table->foreignIdFor(User::class, 'serving_player_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->enum('serving_side', GameLogSide::values())
                ->nullable();
            $table->unsignedTinyInteger('player_one_score')->default(0);
            $table->unsignedTinyInteger('player_two_score')->default(0);
            $table->unsignedTinyInteger('player_one_sets')->default(0);
            $table->unsignedTinyInteger('player_two_sets')->default(0);
            $table->timestamps();

            $table->unique(['game_id', 'sequence']);
            $table->index(['game_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_logs');
    }
};
