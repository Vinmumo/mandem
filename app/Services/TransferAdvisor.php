<?php

namespace App\Services;

use App\Models\Fixture;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Collection;

class TransferAdvisor
{
    public function recommend(Player $outgoing, Collection $squad, int $bank, int $gameweek): Collection
    {
        $owned = $squad->pluck('id');
        $teamCounts = $squad->where('id', '!=', $outgoing->id)->countBy('team_id');
        $budget = $outgoing->price + $bank;

        return Player::query()
            ->where('position', $outgoing->position)
            ->where('price', '<=', $budget)
            ->whereNotIn('id', $owned)
            ->get()
            ->filter(fn (Player $player) => ($teamCounts[$player->team_id] ?? 0) < 3)
            ->map(fn (Player $player) => $this->profile($player, $gameweek, $budget))
            ->filter(fn (array $player) => ($player['chance'] ?? 100) > 0)
            ->sortByDesc('score')
            ->take(8)
            ->values();
    }

    public function profile(Player $player, int $gameweek, ?int $budget = null): array
    {
        $meta = $player->meta ?? [];
        $fixtures = $this->fixturesFor($player->team_id, $gameweek);
        $averageDifficulty = round($fixtures->avg('difficulty') ?: 3, 1);
        $form = (float) ($meta['form'] ?? 0);
        $pointsPerGame = (float) ($meta['points_per_game'] ?? 0);
        $minutes = (int) ($meta['minutes'] ?? 0);
        $chance = $meta['chance_of_playing_next_round'] ?? 100;
        $fixtureScore = max(0, 6 - $averageDifficulty);
        $reliability = min(5, $minutes / 180);
        $score = round(($form * 2.2) + ($pointsPerGame * 1.7) + ($fixtureScore * 1.5) + $reliability, 1);

        $reasons = [];
        if ($averageDifficulty <= 2.5) {
            $reasons[] = 'Strong upcoming fixture run';
        }
        if ($form >= 5) {
            $reasons[] = 'In-form over recent gameweeks';
        }
        if ($minutes >= 270) {
            $reasons[] = 'Reliable recent minutes';
        }
        if ((float) ($meta['selected_by_percent'] ?? 100) < 10) {
            $reasons[] = 'Useful ownership differential';
        }
        if ($budget !== null && $budget - $player->price >= 5) {
            $reasons[] = 'Leaves money available elsewhere';
        }
        if (! $reasons) {
            $reasons[] = 'Balanced form, price and fixtures';
        }

        return [
            'id' => $player->id,
            'name' => $player->web_name,
            'full_name' => $player->name,
            'team' => Team::find($player->team_id)?->short_name ?? '—',
            'team_id' => $player->team_id,
            'position' => $player->position,
            'element_type' => $player->position,
            'price' => $player->price,
            'form' => $form,
            'points' => $player->total_points,
            'points_per_game' => $pointsPerGame,
            'ownership' => (float) ($meta['selected_by_percent'] ?? 0),
            'status' => $meta['status'] ?? 'a',
            'chance' => $chance,
            'news' => $meta['news'] ?? '',
            'score' => $score,
            'fixtures' => $fixtures,
            'reasons' => $reasons,
        ];
    }

    public function fixturesFor(int $teamId, int $gameweek, int $limit = 5): Collection
    {
        $teams = Team::all()->keyBy('id');

        return Fixture::query()->where('gameweek', '>=', $gameweek)
            ->where(fn ($q) => $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId))
            ->orderBy('gameweek')->limit($limit)->get()->map(function (Fixture $fixture) use ($teamId, $teams) {
                $home = $fixture->home_team_id === $teamId;
                $opponentId = $home ? $fixture->away_team_id : $fixture->home_team_id;

                return [
                    'gameweek' => $fixture->gameweek,
                    'opponent' => $teams[$opponentId]?->short_name ?? '—',
                    'venue' => $home ? 'H' : 'A',
                    'difficulty' => $home ? $fixture->home_difficulty : $fixture->away_difficulty,
                    'kickoff_at' => $fixture->kickoff_at,
                ];
            });
    }
}
