<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class LeagueEntry extends Model { public $incrementing=false; protected $guarded=[]; protected function casts():array{return ['meta'=>'array'];} public function gameweeks(){return $this->hasMany(EntryGameweek::class);} }
