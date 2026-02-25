<?php

use App\Models\Event;
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
            $table->unsignedTinyInteger('best_of');
            $table->foreignIdFor(User::class, 'player_one_id')
                ->constrained('users');
            $table->foreignIdFor(User::class, 'player_two_id')
                ->constrained('users');
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
