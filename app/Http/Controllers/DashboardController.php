<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\EntryGameweek;
use App\Models\Gameweek;
use App\Models\LeagueEntry;
use App\Models\Player;
use App\Services\LiveLeagueService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', ['current' => Gameweek::where('is_current', true)->first(), 'standings' => $this->currentStandings(), 'awards' => Award::with('entry')->latest('gameweek_id')->limit(4)->get()]);
    }

    public function standings()
    {
        return Inertia::render('Standings', ['current' => Gameweek::where('is_current', true)->first(), 'standings' => $this->currentStandings()]);
    }

    public function managers()
    {
        return Inertia::render('Managers', ['managers' => LeagueEntry::with(['gameweeks' => fn ($q) => $q->orderBy('gameweek_id')])->get()]);
    }

    public function manager(LeagueEntry $entry)
    {
        return Inertia::render('Manager', ['manager' => $entry, 'history' => $entry->gameweeks()->with('gameweek')->orderBy('gameweek_id')->get()]);
    }

    public function stats()
    {
        return Inertia::render('Stats', ['standings' => $this->currentStandings(), 'awards' => Award::with('entry')->latest()->get()]);
    }

    public function live(LiveLeagueService $liveLeague)
    {
        $rows = collect($this->currentStandings());
        $players = Player::whereIn('id', $rows->flatMap(fn ($r) => collect($r->picks ?? [])->pluck('element')))->get()->keyBy('id');
        $captains = $rows->map(function ($r) use ($players) {
            $pick = collect($r->picks ?? [])->firstWhere('is_captain', true);

            return ['manager' => $r->entry->manager_name, 'player' => $players[$pick['element'] ?? 0]->web_name ?? 'Not available', 'points' => $r->points];
        })->groupBy('player')->map(fn ($g, $name) => ['name' => $name, 'count' => $g->count(), 'managers' => $g->pluck('manager')])->values();
        $ownership = $rows->flatMap(fn ($r) => collect($r->picks ?? [])->pluck('element'))->countBy()->sortDesc()->take(12)->map(fn ($count, $id) => ['name' => $players[$id]->web_name ?? 'Unknown', 'count' => $count, 'percent' => $rows->count() ? round($count / $rows->count() * 100) : 0])->values();
        $current = Gameweek::where('is_current', true)->first();
        $live = $current ? $liveLeague->calculate($current) : ['table' => [], 'support' => []];

        return Inertia::render('Live', ['current' => $current, 'standings' => $rows, 'captains' => $captains, 'ownership' => $ownership, 'liveTable' => $live['table'], 'support' => $live['support']]);
    }

    public function hall()
    {
        return Inertia::render('Hall', ['awards' => Award::with('entry')->get()->groupBy('league_entry_id')->map(fn ($a) => ['manager' => $a->first()->entry->manager_name, 'awards' => $a->count()])->sortByDesc('awards')->values(), 'recentAwards' => Award::with('entry')->latest()->take(12)->get(), 'best' => EntryGameweek::with('entry', 'gameweek')->orderByDesc('points')->first()]);
    }

    private function currentStandings()
    {
        $gw = Gameweek::where('is_current', true)->first();

        return $gw ? EntryGameweek::with('entry')->where('gameweek_id', $gw->id)->orderBy('league_rank')->get() : [];
    }
}
