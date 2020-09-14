<?php

namespace Tests\Feature\Lobby;

use App\Events\Lobby\PlayerSetReady;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadyTest extends TestCase
{
	use RefreshDatabase;

	public function test_a_player_can_set_ready_status()
	{
		Event::fake([PlayerSetReady::class]);

		$lobby = Lobby::factory()->create();
		$player = $lobby->leader;

		$this->assertFalse($player->ready);

		$this->actingAs($player)
			->putJson('/api/ready', ['ready' => true])
			->assertNoContent();

		$this->assertTrue($player->refresh()->ready);

		Event::assertDispatchedTimes(PlayerSetReady::class, 1);
		Event::assertDispatched(PlayerSetReady::class, function ($event) use ($lobby, $player) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['player' => $player->id, 'ready' => true];
		});

		$this->actingAs($player)
			->putJson('/api/ready', ['ready' => false])
			->assertNoContent();

		$this->assertFalse($player->refresh()->ready);

		Event::assertDispatchedTimes(PlayerSetReady::class, 2);
		Event::assertDispatched(PlayerSetReady::class, function ($event) use ($lobby, $player) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['player' => $player->id, 'ready' => false];
		});

		$player->update(['ready' => true]);

		$i = 0;

		Event::assertDispatchedTimes(PlayerSetReady::class, 3);
		Event::assertDispatched(PlayerSetReady::class, function ($event) use (&$i, $lobby, $player) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['player' => $player->id, 'ready' => true]
				&& ++$i === 2;
		});

		$this->actingAs($player)
			->putJson('/api/ready', ['ready' => true])
			->assertNoContent();

		$this->assertTrue($player->refresh()->ready);

		Event::assertDispatchedTimes(PlayerSetReady::class, 3);
	}

	public function test_a_player_who_is_not_in_a_lobby_cannot_set_ready_status()
	{
		$player = Player::factory()->create();

		$this->actingAs($player)
			->putJson('/api/ready', ['ready' => true])
			->assertForbidden();
	}

	public function test_it_requires_a_boolean()
	{
		$this->actingAs(Lobby::factory()->create()->leader)
			->putJson('/api/ready')
			->assertStatus(422)
			->assertJsonValidationErrors([
				'ready' => 'The ready field is required.',
			]);

		$this->putJson('/api/ready', ['ready' => 'no'])
			->assertStatus(422)
			->assertJsonValidationErrors([
				'ready' => 'The ready field must be true or false.',
			]);
	}

	public function test_an_unauthenticated_user_cannot_set_ready_status()
	{
		$this->put('/api/ready')
			->assertRedirect('/');

		$this->putJson('/api/ready')
			->assertUnauthorized();

		$this->assertDatabaseCount('players', 0);
		$this->assertDatabaseCount('lobbies', 0);
	}
}
