<?php

namespace App\Http\Controllers;

use App\Models\Award;
use Inertia\Inertia;

class AwardController extends Controller
{
    public function show(Award $award)
    {
        return Inertia::render('Awards/Show', ['award' => $award->load('entry')]);
    }
}
