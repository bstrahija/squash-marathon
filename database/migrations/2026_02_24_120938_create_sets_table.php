<?php

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
        Schema::create('sets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Game::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'player_one_id')
                ->constrained('users');
            $table->foreignIdFor(User::class, 'player_two_id')
                ->constrained('users');
            $table->foreignIdFor(User::class, 'winner_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->unsignedTinyInteger('player_one_score')->nullable();
            $table->unsignedTinyInteger('player_two_score')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sets');
    }
};
