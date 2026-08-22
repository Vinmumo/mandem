<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferPlan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['squad' => 'array', 'is_active' => 'boolean'];
    }
}
