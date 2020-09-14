<?php

namespace Tests\Feature\Lobby;

use App\Events\Lobby\GameCancelled;
use App\Events\Lobby\GameWillStart;
use App\Events\Lobby\PlayerKicked;
use App\Events\Lobby\PlayerLeft;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KickTest extends TestCase
{
	use RefreshDatabase;

	public function test_leader_can_kick_players()
	{
		Event::fake([PlayerLeft::class, PlayerKicked::class]);

		$lobby = Lobby::factory()->create();
		$player = Player::factory()->create(['lobby_id' => $lobby->id]);

		$this->actingAs($lobby->leader)
			->postJson("/api/lobby/{$lobby->id}/kick", [
				'player' => $player->id,
			])
			->assertNoContent();

		Event::assertDispatchedTimes(PlayerKicked::class, 1);
		Event::assertDispatched(PlayerKicked::class, function ($event) use ($lobby, $player) {
			return $event instanceof ShouldBroadcast
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['player' => $player->only('id', 'name', 'ready')];
		});

		Event::assertDispatchedTimes(PlayerLeft::class, 1);
		Event::assertDispatched(PlayerLeft::class, function ($event) use ($lobby, $player) {
			return $event instanceof ShouldBroadcast
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['player' => $player->only('id', 'name', 'ready')];
		});

		$this->assertCount(1, $lobby->members);
		$this->assertTrue($lobby->members->contains($lobby->leader));
		$this->assertFalse($lobby->members->contains($player));
		$this->assertTrue(Player::withTrashed()->find($player->id)->trashed());
		$this->assertNotNull(Player::withTrashed()->find($player->id)->deleted_at);
	}

	public function test_kicking_autostarts_game_if_applicable()
	{
		Event::fake([GameCancelled::class, GameWillStart::class, PlayerKicked::class]);

		$config = array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'werewolves']);
		$config['werewolves']['playerCount']['min'] = 1;

		$lobby = Lobby::factory()->create(['game_config' => $config]);

		$lobby->leader->update(['ready' => true]);

		Event::assertDispatchedTimes(GameWillStart::class, 1);

		$player = Player::factory()->create(['lobby_id' => $lobby->id]);

		Event::assertDispatchedTimes(GameCancelled::class, 1);

		$this->actingAs($lobby->leader)
			->postJson("/api/lobby/{$lobby->id}/kick", [
				'player' => $player->id,
			])
			->assertNoContent();

		Event::assertDispatchedTimes(PlayerKicked::class, 1);
		Event::assertDispatched(PlayerKicked::class, function ($event) use ($lobby, $player) {
			return $event instanceof ShouldBroadcast
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['player' => $player->only('id', 'name', 'ready')];
		});

		Event::assertDispatchedTimes(GameWillStart::class, 2);
		Event::assertDispatched(GameWillStart::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcast
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});
		Event::assertDispatchedTimes(GameCancelled::class, 1);

		$this->assertCount(1, $lobby->members);
		$this->assertTrue($lobby->members->contains($lobby->leader));
		$this->assertFalse($lobby->members->contains($player));
		$this->assertTrue($player->refresh()->trashed());
		$this->assertNotNull($player->deleted_at);
	}

	public function test_lobby_leader_cannot_be_kicked()
	{
		Event::fake([PlayerLeft::class, PlayerKicked::class]);

		$lobby = Lobby::factory()->create();

		$this->actingAs($lobby->leader)
			->postJson("/api/lobby/{$lobby->id}/kick", [
				'player' => $lobby->leader->id,
			])
			->assertStatus(422)
			->assertJsonValidationErrors([
				'player' => 'The lobby leader cannot be kicked.',
			]);

		Event::assertDispatchedTimes(PlayerLeft::class, 0);
		Event::assertDispatchedTimes(PlayerKicked::class, 0);

		$this->assertDatabaseHas('players', [
			'id' => $lobby->leader->id,
			'lobby_id' => $lobby->id,
			'deleted_at' => null,
		]);
	}

	public function test_non_leaders_cannot_kick_players()
	{
		Event::fake([PlayerLeft::class, PlayerKicked::class]);

		$lobby = Lobby::factory()->create();
		$players = Player::factory()->count(2)->create(['lobby_id' => $lobby->id]);

		$this->actingAs($players[0])
			->postJson("/api/lobby/{$lobby->id}/kick", [
				'player' => $players[1]->id,
			])
			->assertForbidden();

		// test players who are not in lobby
		$this->actingAs(Player::factory()->create())
			->postJson("/api/lobby/{$lobby->id}/kick", [
				'player' => $players[1]->id,
			])
			->assertForbidden();

		Event::assertDispatchedTimes(PlayerLeft::class, 0);
		Event::assertDispatchedTimes(PlayerKicked::class, 0);

		$this->assertDatabaseHas('players', [
			'id' => $players[1]->id,
			'lobby_id' => $lobby->id,
			'deleted_at' => null,
		]);
	}

	public function test_unauthenticated_users_cannot_kick_players()
	{
		Event::fake([PlayerLeft::class, PlayerKicked::class]);

		$lobby = Lobby::factory()->create();
		$player = Player::factory()->create(['lobby_id' => $lobby->id]);

		$this->postJson("/api/lobby/{$lobby->id}/kick", [
			'player' => $player->id,
		])
			->assertUnauthorized();

		Event::assertDispatchedTimes(PlayerLeft::class, 0);
		Event::assertDispatchedTimes(PlayerKicked::class, 0);

		$this->assertDatabaseHas('players', [
			'id' => $player->id,
			'lobby_id' => $lobby->id,
			'deleted_at' => null,
		]);
	}
}
