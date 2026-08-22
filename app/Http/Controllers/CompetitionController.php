<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\LeagueEntry;
use App\Services\CompetitionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CompetitionController extends Controller
{
    public function index(CompetitionService $service)
    {
        $competitions = Competition::with('members')->latest()->get()->map(fn ($competition) => [
            ...$competition->toArray(),
            'summary' => $service->standings($competition),
        ]);

        return Inertia::render('Competitions/Index', ['competitions' => $competitions]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in(['points', 'head_to_head', 'survivor'])],
            'starts_gameweek' => ['required', 'integer', 'between:1,38'],
        ]);
        $competition = Competition::create([...$data, 'created_by' => $request->user()->id]);
        $competition->members()->sync(LeagueEntry::pluck('id'));

        return redirect()->route('competitions.show', $competition)->with('success', 'Competition created.');
    }

    public function show(Competition $competition, CompetitionService $service)
    {
        $competition->load('members');

        return Inertia::render('Competitions/Show', ['competition' => $competition, 'results' => $service->standings($competition)]);
    }
}
