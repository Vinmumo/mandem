<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class Player extends Model { public $incrementing=false; protected $guarded=[]; protected function casts():array{return ['meta'=>'array'];} }
