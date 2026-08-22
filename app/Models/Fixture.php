<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fixture extends Model
{
    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['kickoff_at' => 'datetime', 'finished' => 'boolean', 'meta' => 'array'];
    }
}
