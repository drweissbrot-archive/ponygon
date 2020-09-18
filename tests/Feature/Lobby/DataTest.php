<?php

namespace Tests\Feature\Lobby;

use App\Models\Lobby;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataTest extends TestCase
{
	use RefreshDatabase;

	public function test_players_can_get_data_for_their_lobby()
	{
		$lobby = Lobby::factory()->create();
		$player = Player::factory()->create(['lobby_id' => $lobby->id]);

		$this->actingAs($player)
			->getJson("/api/lobby/{$lobby->id}")
			->assertOk()
			->assertSimilarJson([
				'data' => [
					'id' => $lobby->id,
					'game_config' => Lobby::DEFAULT_CONFIG,
					'invite_url' => $lobby->invite_url,
					'leader_id' => $lobby->leader->id,
					'match' => null,
					'members' => [
						$lobby->leader->only('id', 'name', 'ready'),
						$player->only('id', 'name', 'ready'),
					],
				],
			]);
	}

	public function test_lobby_data_contains_match()
	{
		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);
		$lobby->leader->update(['ready' => true]);
		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		$this->actingAs($player)
			->getJson("/api/lobby/{$lobby->id}")
			->assertOk()
			->assertSimilarJson([
				'data' => [
					'id' => $lobby->id,
					'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
					'invite_url' => $lobby->invite_url,
					'leader_id' => $lobby->leader->id,
					'members' => [
						$lobby->leader->only('id', 'name', 'ready'),
						$player->only('id', 'name', 'ready'),
					],
					'match' => [
						'id' => $lobby->refresh()->match->id,
						'game' => 'tictactoe',
					],
				],
			]);
	}

	public function test_other_lobbies_and_unauthenticated()
	{
		$lobby = Lobby::factory()->create();

		$this->getJson("/api/lobby/{$lobby->id}")
			->assertUnauthorized();

		$this->actingAs(Player::factory()->create())
			->getJson("/api/lobby/{$lobby->id}")
			->assertForbidden();

		$otherLobby = Lobby::factory()->create();

		$this->actingAs(Player::factory()->create(['lobby_id' => $otherLobby->id]))
			->getJson("/api/lobby/{$lobby->id}")
			->assertForbidden();
	}
}
