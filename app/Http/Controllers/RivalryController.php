<?php

namespace App\Http\Controllers;

use App\Models\LeagueEntry;
use App\Services\RivalryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RivalryController extends Controller
{
    public function index(Request $request, RivalryService $rivalries)
    {
        $entries = LeagueEntry::orderBy('manager_name')->get();
        $left = $entries->firstWhere('id', $request->integer('left'))
            ?? $entries->firstWhere('id', $request->user()->fpl_entry_id)
            ?? $entries->first();
        $right = $entries->firstWhere('id', $request->integer('right'))
            ?? $entries->first(fn ($entry) => $entry->id !== $left?->id);

        return Inertia::render('Rivalries', [
            'entries' => $entries,
            'comparison' => $left && $right ? $rivalries->compare($left, $right) : null,
        ]);
    }
}
