<?php

namespace Tests\Feature\Games\TicTacToe;

use App\Events\Lobby\MatchCancelled;
use App\Events\Lobby\MatchStarting;
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
		Event::fake([MatchStarting::class, MatchCancelled::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);
		$lobby->leader->update(['ready' => true]);

		$lobby->refresh();

		Event::assertDispatchedTimes(MatchStarting::class, 1);
		Event::assertDispatched(MatchStarting::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['id' => $lobby->match->id, 'game' => 'tictactoe'];
		});

		Event::assertDispatchedTimes(MatchCancelled::class, 0);

		$this->assertTrue(in_array($lobby->match->state('x'), [$lobby->leader->id, $player->id]));
		$this->assertTrue(in_array($lobby->match->state('o'), [$lobby->leader->id, $player->id]));
		$this->assertFalse($lobby->match->state('x') === $lobby->match->state('o'));

		$this->assertTrue($lobby->match->state('turn') === $lobby->match->state('x'));

		$this->assertEquals(
			[[null, null, null], [null, null, null], [null, null, null]],
			$lobby->match->state('board'),
		);
	}
}
