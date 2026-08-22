<?php

namespace Tests\Feature;

use App\Models\Award;
use App\Models\Competition;
use App\Models\EntryGameweek;
use App\Models\Gameweek;
use App\Models\LeagueEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_live_room_and_rivalry(): void
    {
        [$user] = $this->leagueData();

        $this->actingAs($user)->get('/live')->assertOk();
        $this->actingAs($user)->get('/rivalries')->assertOk();
    }

    public function test_member_can_create_a_competition_for_every_manager(): void
    {
        [$user] = $this->leagueData();

        $response = $this->actingAs($user)->post('/competitions', [
            'name' => 'Mandem H2H', 'type' => 'head_to_head', 'starts_gameweek' => 1,
        ]);

        $competition = Competition::firstOrFail();
        $response->assertRedirect(route('competitions.show', $competition));
        $this->assertCount(2, $competition->members);
        $this->actingAs($user)->get(route('competitions.show', $competition))->assertOk();
    }

    public function test_award_has_a_shareable_card(): void
    {
        [$user, $gameweek, $left] = $this->leagueData();
        $award = Award::create([
            'gameweek_id' => $gameweek->id, 'league_entry_id' => $left->id,
            'type' => 'manager_week', 'title' => 'Manager of the Week',
            'description' => 'Vince · 70 points', 'score' => 70,
        ]);

        $this->actingAs($user)->get(route('awards.show', $award))->assertOk();
    }

    private function leagueData(): array
    {
        $user = User::factory()->create(['fpl_entry_id' => 101]);
        $gameweek = Gameweek::create(['number' => 1, 'name' => 'GW1', 'is_current' => true]);
        $left = LeagueEntry::create(['id' => 101, 'user_id' => $user->id, 'manager_name' => 'Vince', 'team_name' => 'Mandem XI', 'total_points' => 70]);
        $right = LeagueEntry::create(['id' => 102, 'manager_name' => 'Brian', 'team_name' => 'No Salah', 'total_points' => 60]);
        foreach ([[$left, 70, 1], [$right, 60, 2]] as [$entry, $points, $rank]) {
            EntryGameweek::create([
                'gameweek_id' => $gameweek->id, 'league_entry_id' => $entry->id,
                'league_rank' => $rank, 'points' => $points, 'total_points' => $points, 'picks' => [],
            ]);
        }

        return [$user, $gameweek, $left, $right];
    }
}
