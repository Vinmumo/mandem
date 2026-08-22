<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerGameweek extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['stats' => 'array', 'explain' => 'array'];
    }
}
