<?php

namespace Tests\Feature\Game\TicTacToe;

use App\Events\Lobby\GameCancelled;
use App\Events\Lobby\GameStarting;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitTest extends TestCase
{
	use RefreshDatabase;

	public function test_it_initializes()
	{
		Event::fake([GameStarting::class, GameCancelled::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);
		$lobby->leader->update(['ready' => true]);

		$lobby->refresh();

		Event::assertDispatchedTimes(GameStarting::class, 1);
		Event::assertDispatched(GameStarting::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['id' => $lobby->game->id, 'game' => 'tictactoe'];
		});

		Event::assertDispatchedTimes(GameCancelled::class, 0);

		$this->assertTrue($lobby->game->state('x') === $lobby->leader->id || $lobby->game->state('x') === $player->id);
		$this->assertTrue($lobby->game->state('o') === $lobby->leader->id || $lobby->game->state('o') === $player->id);
		$this->assertFalse($lobby->game->state('x') === $lobby->game->state('o'));

		$this->assertTrue($lobby->game->state('turn') === $lobby->game->state('x'));

		$this->assertEquals(
			[[null, null, null], [null, null, null], [null, null, null]],
			$lobby->game->state('board'),
		);
	}
}
