<?php

namespace App\Http\Controllers;

use App\Models\EntryGameweek;
use App\Models\Gameweek;
use App\Models\LeagueEntry;
use App\Models\Player;
use App\Models\TransferPlan;
use App\Services\CaptaincyAdvisor;
use App\Services\TransferAdvisor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TeamLabController extends Controller
{
    public function index(Request $request, TransferAdvisor $advisor)
    {
        $entry = LeagueEntry::where('id', $request->user()->fpl_entry_id)->first();
        $availableEntries = LeagueEntry::whereNull('user_id')->orWhere('user_id', $request->user()->id)
            ->orderBy('manager_name')->get(['id', 'manager_name', 'team_name']);

        if (! $entry) {
            return Inertia::render('TeamLab/Index', ['entry' => null, 'availableEntries' => $availableEntries]);
        }

        if ($entry->user_id !== $request->user()->id) {
            $entry->update(['user_id' => $request->user()->id]);
        }
        $gameweek = Gameweek::where('is_current', true)->first() ?? Gameweek::orderBy('number')->first();
        $snapshot = EntryGameweek::where('league_entry_id', $entry->id)->latest('gameweek_id')->first();
        $plans = TransferPlan::where('user_id', $request->user()->id)->latest()->get();
        $selectedPlan = $request->has('plan')
            ? $plans->firstWhere('id', $request->integer('plan'))
            : $plans->first();
        $rawPicks = collect($snapshot?->picks);
        $squadIds = collect($selectedPlan?->squad ?? $rawPicks->pluck('element'));
        $squad = Player::whereIn('id', $squadIds)->get()->keyBy('id');
        $picks = $squadIds->map(function ($playerId, $index) use ($rawPicks, $squad, $advisor, $gameweek) {
            $player = $squad[$playerId] ?? null;
            $slot = $rawPicks->get($index, ['position' => $index + 1, 'multiplier' => $index < 11 ? 1 : 0]);

            return $player ? [...$advisor->profile($player, $gameweek?->number ?? 1), ...$slot, 'element' => $player->id] : null;
        })->filter()->values();

        $replacementId = $request->integer('replace');
        $outgoing = $replacementId ? $squad->get($replacementId) : null;
        $bank = (int) ($selectedPlan?->bank ?? data_get($snapshot, 'meta.bank', 0));
        $recommendations = $outgoing ? $advisor->recommend($outgoing, $squad->values(), $bank, $gameweek?->number ?? 1) : [];

        return Inertia::render('TeamLab/Index', [
            'entry' => $entry, 'current' => $gameweek, 'snapshot' => $snapshot,
            'picks' => $picks, 'bank' => $bank, 'plans' => $plans,
            'selectedPlan' => $selectedPlan, 'recommendations' => $recommendations,
            'replacing' => $outgoing ? $advisor->profile($outgoing, $gameweek?->number ?? 1) : null,
            'availableEntries' => $availableEntries,
        ]);
    }

    public function player(Player $player, TransferAdvisor $advisor)
    {
        $gameweek = Gameweek::where('is_current', true)->value('number') ?? 1;

        return Inertia::render('TeamLab/Player', ['player' => $advisor->profile($player, $gameweek)]);
    }

    public function captaincy(Request $request, CaptaincyAdvisor $advisor)
    {
        $entry = LeagueEntry::where('id', $request->user()->fpl_entry_id)->firstOrFail();
        $snapshot = EntryGameweek::where('league_entry_id', $entry->id)->latest('gameweek_id')->firstOrFail();
        $plan = $request->integer('plan')
            ? TransferPlan::where('user_id', $request->user()->id)->find($request->integer('plan'))
            : null;
        $ids = collect($plan?->squad ?? collect($snapshot->picks)->pluck('element'));
        $squad = Player::whereIn('id', $ids)->get();
        $gameweek = Gameweek::where('is_current', true)->first() ?? Gameweek::orderBy('number')->firstOrFail();

        return Inertia::render('TeamLab/Captaincy', [
            'entry' => $entry,
            'current' => $gameweek,
            'analysis' => $advisor->analyse($squad, $gameweek->number),
            'plan' => $plan,
        ]);
    }

    public function link(Request $request)
    {
        $data = $request->validate(['fpl_entry_id' => ['required', 'integer', Rule::exists('league_entries', 'id')]]);
        $entry = LeagueEntry::findOrFail($data['fpl_entry_id']);
        abort_if($entry->user_id && $entry->user_id !== $request->user()->id, 422, 'That manager is already linked to another member.');
        LeagueEntry::where('user_id', $request->user()->id)->update(['user_id' => null]);
        $request->user()->update(['fpl_entry_id' => $entry->id]);
        $entry->update(['user_id' => $request->user()->id]);

        return redirect()->route('team-lab')->with('success', "Linked to {$entry->team_name}.");
    }

    public function savePlan(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'], 'name' => ['required', 'string', 'max:60'],
            'squad' => ['required', 'array', 'size:15'], 'squad.*' => ['required', 'integer', 'distinct', Rule::exists('players', 'id')],
            'bank' => ['required', 'integer', 'min:0'], 'free_transfers' => ['required', 'integer', 'between:0,5'],
        ]);
        $players = Player::whereIn('id', $data['squad'])->get();
        $positions = $players->countBy('position');
        $validPositions = $players->count() === 15
            && $positions->get(1) === 2 && $positions->get(2) === 5
            && $positions->get(3) === 5 && $positions->get(4) === 3;
        abort_unless($validPositions, 422, 'Draft must contain 2 goalkeepers, 5 defenders, 5 midfielders and 3 forwards.');
        abort_if($players->countBy('team_id')->max() > 3, 422, 'A draft may contain no more than three players from one club.');
        $plan = isset($data['id']) ? TransferPlan::where('user_id', $request->user()->id)->findOrFail($data['id']) : new TransferPlan(['user_id' => $request->user()->id]);
        $plan->fill(collect($data)->except('id')->all())->save();

        return redirect()->route('team-lab', ['plan' => $plan->id])->with('success', 'Transfer plan saved.');
    }
}
