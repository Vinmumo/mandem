<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class Gameweek extends Model { protected $guarded=[]; protected function casts():array{return ['deadline_at'=>'datetime','is_current'=>'boolean','is_finished'=>'boolean','meta'=>'array'];} }
