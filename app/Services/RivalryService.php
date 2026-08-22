<?php

namespace App\Services;

use App\Models\EntryGameweek;
use App\Models\LeagueEntry;
use App\Models\Player;

class RivalryService
{
    public function compare(LeagueEntry $left, LeagueEntry $right): array
    {
        $leftHistory = EntryGameweek::with('gameweek')->where('league_entry_id', $left->id)->get()->keyBy('gameweek_id');
        $rightHistory = EntryGameweek::with('gameweek')->where('league_entry_id', $right->id)->get()->keyBy('gameweek_id');
        $gameweekIds = $leftHistory->keys()->intersect($rightHistory->keys())->sort();
        $leftWins = 0;
        $rightWins = 0;
        $draws = 0;
        $timeline = $gameweekIds->map(function ($id) use ($leftHistory, $rightHistory, &$leftWins, &$rightWins, &$draws) {
            $a = $leftHistory[$id];
            $b = $rightHistory[$id];
            if ($a->points > $b->points) {
                $leftWins++;
            } elseif ($b->points > $a->points) {
                $rightWins++;
            } else {
                $draws++;
            }

            return ['gameweek' => $a->gameweek->number, 'left' => $a->points, 'right' => $b->points, 'winner' => $a->points <=> $b->points];
        })->values();

        $leftLatest = $leftHistory->sortByDesc('gameweek_id')->first();
        $rightLatest = $rightHistory->sortByDesc('gameweek_id')->first();
        $leftIds = collect($leftLatest?->picks)->pluck('element');
        $rightIds = collect($rightLatest?->picks)->pluck('element');
        $players = Player::whereIn('id', $leftIds->merge($rightIds)->unique())->get()->keyBy('id');
        $names = fn ($ids) => collect($ids)->map(fn ($id) => $players[$id]->web_name ?? 'Unknown')->values();

        return [
            'score' => ['left' => $leftWins, 'right' => $rightWins, 'draws' => $draws],
            'timeline' => $timeline,
            'common' => $names($leftIds->intersect($rightIds)),
            'left_unique' => $names($leftIds->diff($rightIds)),
            'right_unique' => $names($rightIds->diff($leftIds)),
            'left' => $left, 'right' => $right,
            'metrics' => [
                'left_average' => round($leftHistory->avg('points') ?: 0, 1),
                'right_average' => round($rightHistory->avg('points') ?: 0, 1),
                'left_bench' => $leftHistory->sum('bench_points'),
                'right_bench' => $rightHistory->sum('bench_points'),
                'left_hits' => $leftHistory->sum('transfer_cost'),
                'right_hits' => $rightHistory->sum('transfer_cost'),
            ],
        ];
    }
}
