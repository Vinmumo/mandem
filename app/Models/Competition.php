<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['config' => 'array'];
    }

    public function members()
    {
        return $this->belongsToMany(LeagueEntry::class, 'competition_members');
    }
}
