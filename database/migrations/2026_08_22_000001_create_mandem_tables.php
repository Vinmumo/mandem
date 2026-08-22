<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('users', function(Blueprint $t){$t->unsignedBigInteger('fpl_entry_id')->nullable()->unique();$t->boolean('is_admin')->default(false);});
  Schema::create('gameweeks', function(Blueprint $t){$t->id();$t->unsignedSmallInteger('number')->unique();$t->string('name');$t->timestamp('deadline_at')->nullable();$t->boolean('is_current')->default(false);$t->boolean('is_finished')->default(false);$t->json('meta')->nullable();$t->timestamps();});
  Schema::create('players', function(Blueprint $t){$t->unsignedBigInteger('id')->primary();$t->string('name');$t->string('web_name');$t->unsignedTinyInteger('position');$t->unsignedTinyInteger('team_id');$t->unsignedSmallInteger('price')->default(0);$t->integer('total_points')->default(0);$t->json('meta')->nullable();$t->timestamps();});
  Schema::create('league_entries', function(Blueprint $t){$t->unsignedBigInteger('id')->primary();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('manager_name');$t->string('team_name');$t->unsignedInteger('overall_rank')->nullable();$t->unsignedInteger('total_points')->default(0);$t->unsignedSmallInteger('team_value')->nullable();$t->json('meta')->nullable();$t->timestamps();});
  Schema::create('entry_gameweeks', function(Blueprint $t){$t->id();$t->foreignId('gameweek_id')->constrained()->cascadeOnDelete();$t->unsignedBigInteger('league_entry_id');$t->foreign('league_entry_id')->references('id')->on('league_entries')->cascadeOnDelete();$t->unsignedSmallInteger('league_rank');$t->unsignedSmallInteger('previous_rank')->nullable();$t->integer('points');$t->integer('total_points');$t->integer('bench_points')->default(0);$t->unsignedTinyInteger('transfers')->default(0);$t->unsignedTinyInteger('transfer_cost')->default(0);$t->string('chip')->nullable();$t->json('picks')->nullable();$t->json('meta')->nullable();$t->timestamps();$t->unique(['gameweek_id','league_entry_id']);});
  Schema::create('awards', function(Blueprint $t){$t->id();$t->foreignId('gameweek_id')->constrained()->cascadeOnDelete();$t->unsignedBigInteger('league_entry_id');$t->foreign('league_entry_id')->references('id')->on('league_entries')->cascadeOnDelete();$t->string('type');$t->string('title');$t->text('description');$t->decimal('score',10,2)->nullable();$t->timestamps();$t->unique(['gameweek_id','type']);});
  Schema::create('sync_runs', function(Blueprint $t){$t->id();$t->string('status');$t->timestamp('started_at');$t->timestamp('finished_at')->nullable();$t->text('message')->nullable();$t->json('meta')->nullable();$t->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('sync_runs');Schema::dropIfExists('awards');Schema::dropIfExists('entry_gameweeks');Schema::dropIfExists('league_entries');Schema::dropIfExists('players');Schema::dropIfExists('gameweeks');Schema::table('users',fn(Blueprint $t)=>$t->dropColumn(['fpl_entry_id','is_admin']));}
};
