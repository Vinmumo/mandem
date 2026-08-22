<?php

namespace App\Services;

use App\Models\EntryGameweek;
use App\Models\Gameweek;
use App\Models\Player;
use Illuminate\Support\Collection;

class CaptaincyAdvisor
{
    public function __construct(private TransferAdvisor $advisor) {}

    public function analyse(Collection $squad, int $gameweek): array
    {
        $gameweekId = Gameweek::where('number', $gameweek)->value('id');
        $captainCounts = EntryGameweek::query()->where('gameweek_id', $gameweekId)->get()->flatMap(function ($row) {
            return collect($row->picks ?? [])->where('is_captain', true)->pluck('element');
        })->countBy();
        $managerCount = max(1, EntryGameweek::query()->where('gameweek_id', $gameweekId)->count());

        $options = $squad->filter(fn (Player $player) => ($player->meta['chance_of_playing_next_round'] ?? 100) > 0)
            ->map(function (Player $player) use ($gameweek, $captainCounts, $managerCount) {
                $profile = $this->advisor->profile($player, $gameweek);
                $leagueCaptaincy = round(($captainCounts[$player->id] ?? 0) / $managerCount * 100);
                $ownership = $profile['ownership'];
                $base = $profile['score'];

                return [
                    ...$profile,
                    'league_captaincy' => $leagueCaptaincy,
                    'safe_score' => round($base + ($ownership / 20) + ($leagueCaptaincy / 12), 1),
                    'balanced_score' => round($base, 1),
                    'differential_score' => round($base + ((100 - $ownership) / 25) + ((100 - $leagueCaptaincy) / 20), 1),
                    'risk' => $leagueCaptaincy >= 50 ? 'Low' : ($ownership >= 15 ? 'Medium' : 'High'),
                ];
            })->values();

        return [
            'strategies' => [
                'safe' => $options->sortByDesc('safe_score')->first(),
                'balanced' => $options->sortByDesc('balanced_score')->first(),
                'differential' => $options->sortByDesc('differential_score')->first(),
            ],
            'options' => $options->sortByDesc('balanced_score')->values(),
        ];
    }
}
