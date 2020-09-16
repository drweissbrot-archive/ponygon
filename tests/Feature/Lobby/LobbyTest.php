<?php

namespace Tests\Feature\Lobby;

use App\Events\Lobby\PlayerJoined;
use App\Events\Lobby\PlayerLeft;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LobbyTest extends TestCase
{
	use RefreshDatabase;

	public function test_a_player_can_create_a_lobby()
	{
		Lobby::factory()->create();
		$this->travel(1)->seconds();

		$res = $this->actingAs($player = Player::factory()->create())
			->postJson('/api/lobby')
			->assertCreated();

		$this->assertDatabaseCount('lobbies', 2);
		$lobby = Lobby::latest()->first();

		$res->assertExactJson([
			'data' => [
				'id' => $lobby->id,
				'leader_id' => $player->id,
				'game_config' => Lobby::DEFAULT_CONFIG,
				'invite_url' => $lobby->invite_url,
				'members' => [$player->only('id', 'name', 'ready')],
			],
		]);

		$this->assertTrue($lobby->members->contains($player));
		$this->assertEquals($player->id, $lobby->leader_id);
		$this->assertEquals($player->id, $lobby->leader->id);
	}

	public function test_a_player_can_join_a_lobby()
	{
		Event::fake([PlayerJoined::class, PlayerLeft::class]);

		$lobby = Lobby::factory()->create();
		$leader = $lobby->leader;

		Event::assertDispatchedTimes(PlayerJoined::class, 1);
		Event::assertDispatched(PlayerJoined::class, function ($event) use ($lobby, $leader) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['player' => $leader->only('id', 'name', 'ready')];
		});

		$this->actingAs($player = Player::factory()->create())
			->postJson("/api/lobby/{$lobby->id}")
			->assertOk()
			->assertSimilarJson([
				'data' => [
					'id' => $lobby->id,
					'leader_id' => $leader->id,
					'game_config' => Lobby::DEFAULT_CONFIG,
					'invite_url' => $lobby->invite_url,
					'members' => [
						$leader->only('id', 'name', 'ready'),
						$player->only('id', 'name', 'ready'),
					],
				],
			]);

		Event::assertDispatchedTimes(PlayerJoined::class, 2);
		Event::assertDispatched(PlayerJoined::class, function ($event) use ($lobby, $player) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['player' => $player->only('id', 'name', 'ready')];
		});

		$lobby->refresh();
		$leader->refresh();

		$this->assertEquals($leader->id, $lobby->leader_id);
		$this->assertEquals($leader->id, $lobby->leader->id);

		$this->assertCount(2, $lobby->members);
		$this->assertTrue($lobby->members->contains($leader));
		$this->assertTrue($lobby->members->contains($player));

		Event::assertDispatchedTimes(PlayerLeft::class, 0);
	}

	public function test_unauthenticated_users_cannot_create_or_join_lobbies()
	{
		$lobby = Lobby::factory()->create();

		$this->actingAs($lobby->leader)
			->postJson('/api/lobby')
			->assertForbidden();

		$second = Lobby::factory()->create();

		$this->actingAs($lobby->leader)
			->postJson("/api/lobby/{$second->id}")
			->assertForbidden();
	}
}
