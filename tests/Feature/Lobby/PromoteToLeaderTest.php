<?php

namespace Tests\Feature\Lobby;

use App\Events\Lobby\PlayerPromotedToLeader;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoteToLeaderTest extends TestCase
{
	use RefreshDatabase;

	public function test_leader_can_promote_a_player_to_leader()
	{
		Event::fake([PlayerPromotedToLeader::class]);

		$lobby = Lobby::factory()->create();
		$player = Player::factory()->create(['lobby_id' => $lobby->id]);
		$leader = $lobby->leader;

		$this->actingAs($leader)
			->postJson("/api/lobby/{$lobby->id}/promote-to-leader", [
				'player' => $player->id,
			])
			->assertNoContent();

		Event::assertDispatchedTimes(PlayerPromotedToLeader::class, 1);
		Event::assertDispatched(PlayerPromotedToLeader::class, function ($event) use ($lobby, $player) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['player' => $player->only('id', 'name', 'ready')];
		});

		$lobby->refresh();

		$this->assertEquals($player->id, $lobby->leader_id);
		$this->assertEquals($player->id, $lobby->leader->id);

		$this->assertCount(2, $lobby->members);
		$this->assertTrue($lobby->members->contains($leader));
		$this->assertTrue($lobby->members->contains($player));
	}

	public function test_leader_cannot_be_promoted_to_leader()
	{
		Event::fake([PlayerPromotedToLeader::class]);

		$lobby = Lobby::factory()->create();

		$this->actingAs($lobby->leader)
			->postJson("/api/lobby/{$lobby->id}/promote-to-leader", [
				'player' => $lobby->leader->id,
			])
			->assertStatus(422)
			->assertJsonValidationErrors([
				'player' => 'The lobby leader cannot be promoted to lobby leader.',
			]);

		Event::assertDispatchedTimes(PlayerPromotedToLeader::class, 0);

		$this->assertDatabaseHas('lobbies', [
			'id' => $lobby->id,
			'leader_id' => $lobby->leader->id,
		]);
	}

	public function test_non_leaders_cannot_promote_players()
	{
		Event::fake([PlayerPromotedToLeader::class]);

		$lobby = Lobby::factory()->create();
		$players = Player::factory()->count(2)->create(['lobby_id' => $lobby->id]);

		$this->actingAs($players[0])
			->postJson("/api/lobby/{$lobby->id}/promote-to-leader", [
				'player' => $players[1]->id,
			])
			->assertForbidden();

		// test players who are not in lobby
		$this->actingAs(Player::factory()->create())
			->postJson("/api/lobby/{$lobby->id}/promote-to-leader", [
				'player' => $players[1]->id,
			])
			->assertForbidden();

		Event::assertDispatchedTimes(PlayerPromotedToLeader::class, 0);

		$this->assertDatabaseHas('lobbies', [
			'id' => $lobby->id,
			'leader_id' => $lobby->leader->id,
		]);
	}

	public function test_unauthenticated_users_cannot_promote_players()
	{
		Event::fake([PlayerPromotedToLeader::class]);

		$lobby = Lobby::factory()->create();
		$player = Player::factory()->create(['lobby_id' => $lobby->id]);

		$this->postJson("/api/lobby/{$lobby->id}/promote-to-leader", [
			'player' => $player->id,
		])
			->assertUnauthorized();

		Event::assertDispatchedTimes(PlayerPromotedToLeader::class, 0);

		$this->assertDatabaseHas('lobbies', [
			'id' => $lobby->id,
			'leader_id' => $lobby->leader->id,
		]);
	}
}
