<?php

namespace App\Services;

use App\Models\EntryGameweek;
use App\Models\Fixture;
use App\Models\Gameweek;
use App\Models\Player;
use App\Models\PlayerGameweek;

class LiveLeagueService
{
    public function calculate(Gameweek $gameweek): array
    {
        $rows = EntryGameweek::with('entry')->where('gameweek_id', $gameweek->id)->get();
        $playerIds = $rows->flatMap(fn ($row) => collect($row->picks ?? [])->pluck('element'))->unique();
        $players = Player::whereIn('id', $playerIds)->get()->keyBy('id');
        $live = PlayerGameweek::where('gameweek_id', $gameweek->id)->whereIn('player_id', $playerIds)->get()->keyBy('player_id');
        $finishedTeams = Fixture::where('gameweek', $gameweek->number)->get()->flatMap(
            fn ($fixture) => $fixture->finished ? [$fixture->home_team_id, $fixture->away_team_id] : []
        )->flip();

        $table = $rows->map(function ($row) use ($players, $live, $finishedTeams) {
            $active = collect($row->picks ?? [])->filter(fn ($pick) => ($pick['multiplier'] ?? 0) > 0);
            $livePoints = $active->sum(fn ($pick) => ($live[$pick['element']]->points ?? 0) * $pick['multiplier']) - $row->transfer_cost;
            $remaining = $active->filter(function ($pick) use ($players, $finishedTeams) {
                $teamId = $players[$pick['element']]->team_id ?? null;

                return $teamId && ! isset($finishedTeams[$teamId]);
            })->count();

            return [
                'entry_id' => $row->league_entry_id,
                'manager' => $row->entry->manager_name,
                'team' => $row->entry->team_name,
                'old_rank' => $row->league_rank,
                'gameweek_points' => $livePoints,
                'provisional_total' => $row->total_points - $row->points + $livePoints,
                'remaining' => $remaining,
                'picks' => $row->picks,
            ];
        })->sortByDesc('provisional_total')->values()->map(function ($row, $index) {
            $row['live_rank'] = $index + 1;
            $row['movement'] = $row['old_rank'] - $row['live_rank'];

            return $row;
        });

        $support = $playerIds->map(function ($playerId) use ($players, $live, $table) {
            $owners = $table->filter(fn ($row) => collect($row['picks'])->contains(fn ($pick) => $pick['element'] === $playerId && ($pick['multiplier'] ?? 0) > 0));
            if ($owners->isEmpty()) {
                return null;
            }
            $captainers = $table->filter(fn ($row) => collect($row['picks'])->contains(fn ($pick) => $pick['element'] === $playerId && ($pick['is_captain'] ?? false)));

            return [
                'id' => $playerId,
                'name' => $players[$playerId]->web_name ?? 'Unknown',
                'points' => $live[$playerId]->points ?? 0,
                'owners' => $owners->pluck('manager')->values(),
                'captainers' => $captainers->pluck('manager')->values(),
                'swing' => $owners->sum(function ($row) use ($playerId) {
                    $pick = collect($row['picks'])->firstWhere('element', $playerId);

                    return 5 * ($pick['multiplier'] ?? 0);
                }),
            ];
        })->filter()->sortByDesc(fn ($player) => count($player['captainers']) * 3 + count($player['owners']))->take(12)->values();

        return ['table' => $table, 'support' => $support, 'players' => $players, 'live' => $live];
    }
}
