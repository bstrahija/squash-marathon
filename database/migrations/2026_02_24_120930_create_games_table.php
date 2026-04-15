<?php

use App\Models\Event;
use App\Models\Group;
use App\Models\Round;
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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Event::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Group::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Round::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('best_of');
            $table->unsignedTinyInteger('player_one_sets')->default(0);
            $table->unsignedTinyInteger('player_two_sets')->default(0);
            $table->foreignIdFor(User::class, 'player_one_id')
                ->constrained('users');
            $table->foreignIdFor(User::class, 'player_two_id')
                ->constrained('users');
            $table->foreignIdFor(User::class, 'winner_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('is_draw')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
