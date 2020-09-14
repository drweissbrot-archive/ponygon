<?php

namespace Tests\Feature\Lobby;

use App\Events\Lobby\GameConfigChanged;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameConfigTest extends TestCase
{
	use RefreshDatabase;

	public function test_lobby_leader_can_set_game_settings()
	{
		Event::fake([GameConfigChanged::class]);

		$lobby = Lobby::factory()->create();

		$this->assertIsArray($lobby->game_config);
		$this->assertNull($lobby->game_config['selected_game']);

		$this->assertArrayHasKey('werewolves', $lobby->game_config);
		$this->assertEquals(
			json_encode(Lobby::DEFAULT_CONFIG['werewolves']),
			json_encode($lobby->game_config['werewolves']),
		);

		Event::assertDispatchedTimes(GameConfigChanged::class, 0);

		$this->actingAs($lobby->leader)
			->patchJson("/api/lobby/{$lobby->id}/game-config", [
				'selected_game' => 'werewolves',
			])
			->assertNoContent();

		$lobby->refresh();

		$this->assertEquals('werewolves', $lobby->game_config['selected_game']);
		$this->assertEquals(
			json_encode(Lobby::DEFAULT_CONFIG['werewolves']),
			json_encode($lobby->game_config['werewolves']),
		);

		Event::assertDispatchedTimes(GameConfigChanged::class, 1);
		Event::assertDispatched(GameConfigChanged::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& json_encode($event->broadcastWith()) === json_encode([
					'selected_game' => 'werewolves',
					'werewolves' => Lobby::DEFAULT_CONFIG['werewolves'],
				]);
		});

		$this->patchJson("/api/lobby/{$lobby->id}/game-config", [
			'werewolves.amor' => false,
		])
			->assertNoContent();

		$lobby->refresh();

		Event::assertDispatchedTimes(GameConfigChanged::class, 2);
		Event::assertDispatched(GameConfigChanged::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& json_encode($event->broadcastWith()) === json_encode([
					'selected_game' => 'werewolves',
					'werewolves' => array_merge(Lobby::DEFAULT_CONFIG['werewolves'], ['amor' => false]),
				]);
		});

		$this->assertEquals('werewolves', $lobby->game_config['selected_game']);
		$this->assertEquals(
			json_encode(array_merge(Lobby::DEFAULT_CONFIG['werewolves'], ['amor' => false])),
			json_encode($lobby->game_config['werewolves']),
		);
	}

	public function test_one_cannot_select_games_that_do_not_exist()
	{
		Event::fake([GameConfigChanged::class]);

		$lobby = Lobby::factory()->create();

		$this->actingAs($lobby->leader)
			->patchJson("/api/lobby/{$lobby->id}/game-config", [
				'selected_game' => 'invalid',
			])
			->assertJsonValidationErrors([
				'selected_game' => 'The selected selected game is invalid.',
			]);

		Event::assertDispatchedTimes(GameConfigChanged::class, 0);
	}

	public function test_one_cannot_add_config_keys_that_did_not_exist_before()
	{
		Event::fake([GameConfigChanged::class]);

		$lobby = Lobby::factory()->create();

		$this->actingAs($lobby->leader)
			->patchJson("/api/lobby/{$lobby->id}/game-config", [
				'werewolves.invalid' => 'value',
			])
			->assertJsonValidationErrors([
				'game_options' => 'You must provide a game option to change.',
			]);

		Event::assertDispatchedTimes(GameConfigChanged::class, 0);
	}

	public function test_one_cannot_add_or_modify_player_counts()
	{
		Event::fake([GameConfigChanged::class]);

		$lobby = Lobby::factory()->create();

		$this->actingAs($lobby->leader)
			->patchJson("/api/lobby/{$lobby->id}/game-config", [
				'werewolves.playerCount' => ['min' => 0],
			])
			->assertJsonValidationErrors([
				'game_options' => 'You must provide a game option to change.',
			]);

		$this->actingAs($lobby->leader)
			->patchJson("/api/lobby/{$lobby->id}/game-config", [
				'werewolves.playerCount.min' => 2,
			])
			->assertJsonValidationErrors([
				'game_options' => 'You must provide a game option to change.',
			]);

		$this->actingAs($lobby->leader)
			->patchJson("/api/lobby/{$lobby->id}/game-config", [
				'werewolves.playerCount.max' => 2,
			])
			->assertJsonValidationErrors([
				'game_options' => 'You must provide a game option to change.',
			]);

		Event::assertDispatchedTimes(GameConfigChanged::class, 0);
	}

	public function test_only_lobby_leader_can_do_things()
	{
		$lobby = Lobby::factory()->create();

		// unauthenticated
		$this->patchJson("/api/lobby/{$lobby->id}/game-config", [
			'selected_game' => 'werewolves',
		])
			->assertUnauthorized();

		$this->assertDatabaseHas('lobbies', [
			'id' => $lobby->id,
			'game_config' => json_encode(Lobby::DEFAULT_CONFIG),
		]);

		// player out of lobby
		$this->actingAs(Player::factory()->create())
			->patchJson("/api/lobby/{$lobby->id}/game-config", [
				'selected_game' => 'werewolves',
			])
			->assertForbidden();

		$this->assertDatabaseHas('lobbies', [
			'id' => $lobby->id,
			'game_config' => json_encode(Lobby::DEFAULT_CONFIG),
		]);

		// player in the lobby
		$this->actingAs(Player::factory()->create(['lobby_id' => $lobby->id]))
			->patchJson("/api/lobby/{$lobby->id}/game-config", [
				'selected_game' => 'werewolves',
			])
			->assertForbidden();

		$this->assertDatabaseHas('lobbies', [
			'id' => $lobby->id,
			'game_config' => json_encode(Lobby::DEFAULT_CONFIG),
		]);

		// leader of another lobby
		$this->actingAs(Lobby::factory()->create()->leader)
			->patchJson("/api/lobby/{$lobby->id}/game-config", [
				'selected_game' => 'werewolves',
			])
			->assertForbidden();

		$this->assertDatabaseHas('lobbies', [
			'id' => $lobby->id,
			'game_config' => json_encode(Lobby::DEFAULT_CONFIG),
		]);
	}
}
