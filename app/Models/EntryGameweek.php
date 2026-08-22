<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class EntryGameweek extends Model { protected $guarded=[]; protected function casts():array{return ['picks'=>'array','meta'=>'array'];} public function entry(){return $this->belongsTo(LeagueEntry::class,'league_entry_id');} public function gameweek(){return $this->belongsTo(Gameweek::class);} }
