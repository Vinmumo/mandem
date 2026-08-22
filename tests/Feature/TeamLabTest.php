<?php

namespace Tests\Feature;

use App\Models\EntryGameweek;
use App\Models\Gameweek;
use App\Models\LeagueEntry;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamLabTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_claim_a_league_entry(): void
    {
        $user = User::factory()->create();
        $entry = LeagueEntry::create(['id' => 44, 'manager_name' => 'Vince', 'team_name' => 'Mandem XI']);

        $this->actingAs($user)->post('/team-lab/link', ['fpl_entry_id' => $entry->id])
            ->assertRedirect('/team-lab');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'fpl_entry_id' => $entry->id]);
        $this->assertDatabaseHas('league_entries', ['id' => $entry->id, 'user_id' => $user->id]);
    }

    public function test_member_can_view_squad_and_save_a_valid_transfer_plan(): void
    {
        $user = User::factory()->create(['fpl_entry_id' => 44]);
        $entry = LeagueEntry::create(['id' => 44, 'user_id' => $user->id, 'manager_name' => 'Vince', 'team_name' => 'Mandem XI']);
        $gameweek = Gameweek::create(['number' => 1, 'name' => 'GW1', 'is_current' => true]);
        $positions = [1, 1, 2, 2, 2, 2, 2, 3, 3, 3, 3, 3, 4, 4, 4];
        $players = collect($positions)->map(fn ($position, $index) => Player::create([
            'id' => $index + 1, 'name' => "Player {$index}", 'web_name' => "P{$index}",
            'position' => $position, 'team_id' => ($index % 5) + 1, 'price' => 50, 'total_points' => 0, 'meta' => [],
        ]));
        EntryGameweek::create([
            'gameweek_id' => $gameweek->id, 'league_entry_id' => $entry->id, 'league_rank' => 1,
            'points' => 50, 'total_points' => 50, 'picks' => $players->values()->map(fn ($player, $i) => [
                'element' => $player->id, 'position' => $i + 1, 'multiplier' => $i < 11 ? 1 : 0,
                'is_captain' => $i === 9, 'is_vice_captain' => $i === 10,
            ])->all(),
        ]);

        $this->actingAs($user)->get('/team-lab')->assertOk();
        $this->actingAs($user)->get('/team-lab/captaincy')->assertOk();
        $this->actingAs($user)->post('/team-lab/plans', [
            'name' => 'GW2 attack', 'squad' => $players->pluck('id')->all(),
            'bank' => 10, 'free_transfers' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('transfer_plans', ['user_id' => $user->id, 'name' => 'GW2 attack', 'bank' => 10]);
    }
}
