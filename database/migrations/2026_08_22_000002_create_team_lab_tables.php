<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('name');
            $table->string('short_name', 4);
            $table->unsignedTinyInteger('strength')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
        Schema::create('fixtures', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedSmallInteger('gameweek')->nullable()->index();
            $table->unsignedTinyInteger('home_team_id');
            $table->unsignedTinyInteger('away_team_id');
            $table->unsignedTinyInteger('home_difficulty');
            $table->unsignedTinyInteger('away_difficulty');
            $table->timestamp('kickoff_at')->nullable();
            $table->boolean('finished')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
        Schema::create('transfer_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('My draft');
            $table->json('squad');
            $table->unsignedSmallInteger('bank')->default(0);
            $table->unsignedTinyInteger('free_transfers')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_plans');
        Schema::dropIfExists('fixtures');
        Schema::dropIfExists('teams');
    }
};
