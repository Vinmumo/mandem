<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\EntryGameweek;
use Illuminate\Support\Collection;

class CompetitionService
{
    public function standings(Competition $competition): array
    {
        $members = $competition->members;
        $rows = EntryGameweek::with('gameweek')->whereIn('league_entry_id', $members->pluck('id'))
            ->whereHas('gameweek', fn ($query) => $query->where('number', '>=', $competition->starts_gameweek))
            ->get();

        return match ($competition->type) {
            'head_to_head' => $this->headToHead($members, $rows, $competition->starts_gameweek),
            'survivor' => $this->survivor($members, $rows),
            default => $this->pointsTable($members, $rows),
        };
    }

    private function pointsTable(Collection $members, Collection $rows): array
    {
        $table = $members->map(function ($member) use ($rows) {
            $scores = $rows->where('league_entry_id', $member->id);

            return ['entry_id' => $member->id, 'manager' => $member->manager_name, 'team' => $member->team_name, 'points' => $scores->sum('points'), 'played' => $scores->count()];
        })->sortByDesc('points')->values()->map(function ($row, $index) {
            $row['rank'] = $index + 1;

            return $row;
        });

        return ['mode' => 'points', 'table' => $table];
    }

    private function headToHead(Collection $members, Collection $rows, int $start): array
    {
        $ids = $members->pluck('id')->values()->all();
        if (count($ids) % 2) {
            $ids[] = null;
        }
        $rounds = $this->roundRobin($ids);
        $table = $members->mapWithKeys(fn ($member) => [$member->id => ['entry_id' => $member->id, 'manager' => $member->manager_name, 'team' => $member->team_name, 'played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'for' => 0, 'points' => 0]])->all();
        $matches = [];
        foreach ($rows->groupBy('gameweek_id')->sortKeys()->values() as $offset => $weekRows) {
            $round = $rounds[$offset % count($rounds)] ?? [];
            foreach ($round as [$a, $b]) {
                if (! $a || ! $b) {
                    continue;
                }
                $scoreA = (int) ($weekRows->firstWhere('league_entry_id', $a)?->points ?? 0);
                $scoreB = (int) ($weekRows->firstWhere('league_entry_id', $b)?->points ?? 0);
                $table[$a]['played']++;
                $table[$b]['played']++;
                $table[$a]['for'] += $scoreA;
                $table[$b]['for'] += $scoreB;
                if ($scoreA > $scoreB) {
                    $table[$a]['wins']++;
                    $table[$a]['points'] += 3;
                    $table[$b]['losses']++;
                } elseif ($scoreB > $scoreA) {
                    $table[$b]['wins']++;
                    $table[$b]['points'] += 3;
                    $table[$a]['losses']++;
                } else {
                    $table[$a]['draws']++;
                    $table[$b]['draws']++;
                    $table[$a]['points']++;
                    $table[$b]['points']++;
                }
                $matches[] = ['gameweek' => $start + $offset, 'left' => $members->firstWhere('id', $a)?->manager_name, 'right' => $members->firstWhere('id', $b)?->manager_name, 'left_score' => $scoreA, 'right_score' => $scoreB];
            }
        }
        $table = collect($table)->sortByDesc(fn ($row) => [$row['points'], $row['for']])->values()->map(function ($row, $index) {
            $row['rank'] = $index + 1;

            return $row;
        });

        return ['mode' => 'head_to_head', 'table' => $table, 'matches' => collect($matches)->take(-6)->values()];
    }

    private function survivor(Collection $members, Collection $rows): array
    {
        $active = $members->keyBy('id');
        $eliminations = [];
        foreach ($rows->groupBy('gameweek_id')->sortKeys() as $weekRows) {
            if ($active->count() <= 1) {
                break;
            }
            $eligible = $weekRows->whereIn('league_entry_id', $active->keys());
            if ($eligible->count() < $active->count()) {
                continue;
            }
            $loser = $eligible->sortBy(fn ($row) => [$row->points, -$row->total_points])->first();
            $member = $active->pull($loser->league_entry_id);
            $eliminations[] = ['gameweek' => $loser->gameweek->number, 'manager' => $member->manager_name, 'score' => $loser->points];
        }

        return ['mode' => 'survivor', 'survivors' => $active->values()->map(fn ($m) => ['entry_id' => $m->id, 'manager' => $m->manager_name, 'team' => $m->team_name]), 'eliminations' => $eliminations];
    }

    private function roundRobin(array $ids): array
    {
        $rounds = [];
        $count = count($ids);
        for ($round = 0; $round < $count - 1; $round++) {
            $pairs = [];
            for ($i = 0; $i < $count / 2; $i++) {
                $pairs[] = [$ids[$i], $ids[$count - 1 - $i]];
            }
            $rounds[] = $pairs;
            $fixed = array_shift($ids);
            $last = array_pop($ids);
            array_unshift($ids, $fixed, $last);
        }

        return $rounds;
    }
}
