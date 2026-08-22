<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class Award extends Model { protected $guarded=[]; public function entry(){return $this->belongsTo(LeagueEntry::class,'league_entry_id');} }
