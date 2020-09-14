<?php

namespace Tests\Feature\Player;

use App\Events\Lobby\PlayerLeft;
use App\Events\Lobby\PlayerPromotedToLeader;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
	use RefreshDatabase;

	public function test_a_player_can_log_out()
	{
		$player = Player::factory()->create();

		$this->actingAs($player);
		$this->assertAuthenticatedAs($player);

		$this->postJson('/logout')
			->assertNoContent();

		$this->assertGuest();

		$this->assertDatabaseCount('players', 1);
		$this->assertSoftDeleted('players', [
			'id' => $player->id,
		]);
	}

	public function test_a_player_in_a_lobby_can_log_out()
	{
		Event::fake([PlayerLeft::class]);

		$lobby = Lobby::factory()->create();
		$player = Player::factory()->create(['lobby_id' => $lobby->id]);

		$this->actingAs($player);
		$this->assertAuthenticatedAs($player);

		$this->postJson('/logout')
			->assertNoContent();

		$this->assertGuest();

		$this->assertDatabaseCount('players', 2);
		$this->assertSoftDeleted('players', [
			'id' => $player->id,
		]);

		$this->assertDatabaseHas('players', [
			'id' => $lobby->leader->id,
			'deleted_at' => null,
		]);

		Event::assertDispatchedTimes(PlayerLeft::class, 1);
		Event::assertDispatched(PlayerLeft::class, function ($event) use ($lobby, $player) {
			return $event instanceof ShouldBroadcast
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['player' => $player->only('id', 'name', 'ready')];
		});
	}

	public function test_someone_is_promoted_to_leader_when_leader_leaves()
	{
		Event::fake([PlayerLeft::class, PlayerPromotedToLeader::class]);

		$lobby = Lobby::factory()->create();
		$player = Player::factory()->create(['lobby_id' => $lobby->id]);
		$leader = $lobby->leader;

		$this->actingAs($leader);
		$this->assertAuthenticatedAs($leader);

		$this->postJson('/logout')
			->assertNoContent();

		$this->assertGuest();

		$this->assertDatabaseCount('players', 2);
		$this->assertSoftDeleted('players', [
			'id' => $leader->id,
		]);

		$this->assertDatabaseHas('players', [
			'id' => $player->id,
			'deleted_at' => null,
		]);

		$lobby->refresh();

		$this->assertEquals($player->id, $lobby->leader_id);
		$this->assertEquals($player->id, $lobby->leader->id);

		$this->assertCount(1, $lobby->members);
		$this->assertTrue($lobby->members->contains($player));

		Event::assertDispatchedTimes(PlayerLeft::class, 1);
		Event::assertDispatched(PlayerLeft::class, function ($event) use ($lobby, $leader) {
			return $event instanceof ShouldBroadcast
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['player' => $leader->only('id', 'name', 'ready')];
		});

		Event::assertDispatchedTimes(PlayerPromotedToLeader::class, 1);
		Event::assertDispatched(PlayerPromotedToLeader::class, function ($event) use ($lobby, $player) {
			return $event instanceof ShouldBroadcast
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['player' => $player->only('id', 'name', 'ready')];
		});
	}

	public function test_lobby_is_closed_when_everyone_leaves()
	{
		$lobby = Lobby::factory()->create();

		$this->actingAs($lobby->leader)
			->postJson('/logout')
			->assertNoContent();

		$this->assertGuest();

		$this->assertDatabaseCount('players', 1);
		$this->assertSoftDeleted('players', [
			'id' => $lobby->leader->id,
		]);

		$lobby = Lobby::withTrashed()->find($lobby->id);

		$this->assertTrue($lobby->trashed());
		$this->assertNotNull($lobby->deleted_at);
	}
}
