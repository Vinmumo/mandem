<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_gameweeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gameweek_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('player_id');
            $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();
            $table->integer('points')->default(0);
            $table->unsignedSmallInteger('minutes')->default(0);
            $table->json('stats')->nullable();
            $table->json('explain')->nullable();
            $table->timestamps();
            $table->unique(['gameweek_id', 'player_id']);
        });

        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->unsignedSmallInteger('starts_gameweek')->default(1);
            $table->string('status')->default('active');
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('competition_members', function (Blueprint $table) {
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('league_entry_id');
            $table->foreign('league_entry_id')->references('id')->on('league_entries')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['competition_id', 'league_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_members');
        Schema::dropIfExists('competitions');
        Schema::dropIfExists('player_gameweeks');
    }
};
