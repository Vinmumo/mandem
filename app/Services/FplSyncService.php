<?php

namespace App\Services;

use App\Models\Award;
use App\Models\EntryGameweek;
use App\Models\Fixture;
use App\Models\Gameweek;
use App\Models\LeagueEntry;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FplSyncService
{
    public function __construct(private FplClient $fpl) {}

    public function sync(): array
    {
        $bootstrap = $this->fpl->bootstrap();
        $fixtures = $this->fpl->fixtures();
        $league = $this->fpl->standings((int) config('services.fpl.league_id'));

        return DB::transaction(function () use ($bootstrap, $fixtures, $league) {
            foreach ($bootstrap['events'] ?? [] as $e) {
                Gameweek::updateOrCreate(['number' => $e['id']], ['name' => $e['name'], 'deadline_at' => $e['deadline_time'] ?? null, 'is_current' => $e['is_current'] ?? false, 'is_finished' => $e['finished'] ?? false, 'meta' => $e]);
            }
            foreach ($bootstrap['teams'] ?? [] as $t) {
                Team::updateOrCreate(['id' => $t['id']], ['name' => $t['name'], 'short_name' => $t['short_name'], 'strength' => $t['strength'] ?? 0, 'meta' => $t]);
            }
            foreach ($bootstrap['elements'] ?? [] as $p) {
                Player::updateOrCreate(['id' => $p['id']], ['name' => trim(($p['first_name'] ?? '').' '.($p['second_name'] ?? '')), 'web_name' => $p['web_name'], 'position' => $p['element_type'], 'team_id' => $p['team'], 'price' => $p['now_cost'], 'total_points' => $p['total_points'], 'meta' => $p]);
            }
            foreach ($fixtures as $f) {
                Fixture::updateOrCreate(['id' => $f['id']], ['gameweek' => $f['event'] ?? null, 'home_team_id' => $f['team_h'], 'away_team_id' => $f['team_a'], 'home_difficulty' => $f['team_h_difficulty'], 'away_difficulty' => $f['team_a_difficulty'], 'kickoff_at' => $f['kickoff_time'] ?? null, 'finished' => $f['finished'] ?? false, 'meta' => $f]);
            }
            $current = Gameweek::where('is_current', true)->first() ?? Gameweek::orderBy('number')->first();
            foreach ($league['results'] as $row) {
                $detail = $this->fpl->entry($row['entry']);
                $entry = LeagueEntry::updateOrCreate(['id' => $row['entry']], ['user_id' => User::where('fpl_entry_id', $row['entry'])->value('id'), 'manager_name' => $row['player_name'], 'team_name' => $row['entry_name'], 'overall_rank' => $detail['summary_overall_rank'] ?? null, 'total_points' => $row['total'], 'team_value' => $detail['last_deadline_value'] ?? null, 'meta' => $detail]);
                if ($current) {
                    $picks = $this->fpl->picks($entry->id, $current->number);
                    $history = collect($this->fpl->history($entry->id)['current'] ?? [])->firstWhere('event', $current->number) ?? [];
                    EntryGameweek::updateOrCreate(['gameweek_id' => $current->id, 'league_entry_id' => $entry->id], ['league_rank' => $row['rank'], 'previous_rank' => $row['last_rank'] ?? null, 'points' => $row['event_total'], 'total_points' => $row['total'], 'bench_points' => $history['points_on_bench'] ?? 0, 'transfers' => $history['event_transfers'] ?? 0, 'transfer_cost' => $history['event_transfers_cost'] ?? 0, 'chip' => $picks['active_chip'] ?? null, 'picks' => $picks['picks'] ?? [], 'meta' => $picks['entry_history'] ?? []]);
                }
            }
            if ($current) {
                $this->awards($current);
            }

            return ['league' => $league['league']['name'] ?? 'Mandem', 'members' => count($league['results']), 'gameweek' => $current?->number];
        });
    }

    private function awards(Gameweek $gw): void
    {
        $rows = EntryGameweek::where('gameweek_id', $gw->id)->get();
        if ($rows->isEmpty()) {
            return;
        }
        $defs = [['manager_week', 'Manager of the Week', $rows->sortByDesc('points')->first(), 'points', 'points'], ['bench_disaster', 'Bench Disaster', $rows->sortByDesc('bench_points')->first(), 'bench_points', 'points left on the bench'], ['biggest_climber', 'Biggest Climber', $rows->sortByDesc(fn ($r) => ($r->previous_rank ?? $r->league_rank) - $r->league_rank)->first(), null, 'places climbed']];
        foreach ($defs as [$type,$title,$row,$field,$suffix]) {
            $score = $field ? $row->{$field} : ($row->previous_rank ?? $row->league_rank) - $row->league_rank;
            Award::updateOrCreate(['gameweek_id' => $gw->id, 'type' => $type], ['league_entry_id' => $row->league_entry_id, 'title' => $title, 'description' => "{$row->entry->manager_name} · {$score} {$suffix}", 'score' => $score]);
        }
    }
}
