<?php

namespace Tests\Feature\Games\TicTacToe;

use App\Events\Player\MatchData;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchEndTest extends TestCase
{
	use RefreshDatabase;

	public function test_winner_is_declared()
	{
		Event::fake([MatchData::class]);

		[$lobby, $player] = $this->createLobbyWithGame();
		$lobby->match->state()->set('board', [
			['x', null, 'o'], [null, 'x', 'o'], [null, null, null],
		]);

		$this->actingAs($player)
			->postJson("/api/match/{$lobby->match->id}/move", ['x' => 2, 'y' => 2])
			->assertNoContent();

		Event::assertDispatched(MatchData::class, 2);
		Event::assertDispatched(MatchData::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-player.{$lobby->leader->id}"
				&& $event->broadcastWith() === $lobby->leader->matchData();
		});
		Event::assertDispatched(MatchData::class, function ($event) use ($lobby, $player) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-player.{$player->id}"
				&& $event->broadcastWith() === $player->matchData();
		});

		$lobby->match->refresh();

		$this->assertEquals($lobby->leader->id, $lobby->match->state('turn'));
		$this->assertEquals(
			[['x', null, 'o'], [null, 'x', 'o'], [null, null, 'x']],
			$lobby->match->state('board'),
		);

		$this->assertSimilar([
			'scoreboard' => [
				$player->id => ['role' => 'X', 'score' => 1],
				$lobby->leader->id => ['role' => 'O', 'score' => 0],
			],

			'board' => [['x', null, 'o'], [null, 'x', 'o'], [null, null, 'x']],
			'turn' => $lobby->leader->id,
			'winner' => $player->id,
		], $player->matchData());
	}

	public function test_it_declares_ties()
	{
		Event::fake([MatchData::class]);

		[$lobby, $player] = $this->createLobbyWithGame();
		$lobby->match->state()->set('board', [['x', 'o', 'o'], ['o', 'x', 'x'], ['o', null, 'o']]);

		$this->actingAs($player)
			->postJson("/api/match/{$lobby->match->id}/move", ['x' => 2, 'y' => 1])
			->assertNoContent();

		Event::assertDispatched(MatchData::class, 2);
		Event::assertDispatched(MatchData::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-player.{$lobby->leader->id}"
				&& $event->broadcastWith() === $lobby->leader->matchData();
		});
		Event::assertDispatched(MatchData::class, function ($event) use ($lobby, $player) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-player.{$player->id}"
				&& $event->broadcastWith() === $player->matchData();
		});

		$lobby->match->refresh();

		$this->assertEquals($lobby->leader->id, $lobby->match->state('turn'));
		$this->assertEquals(
			[['x', 'o', 'o'], ['o', 'x', 'x'], ['o', 'x', 'o']],
			$lobby->match->state('board'),
		);

		$this->assertSimilar([
			'scoreboard' => [
				$player->id => ['role' => 'X', 'score' => 0],
				$lobby->leader->id => ['role' => 'O', 'score' => 0],
			],

			'board' => [['x', 'o', 'o'], ['o', 'x', 'x'], ['o', 'x', 'o']],
			'turn' => $lobby->leader->id,
			'winner' => 'tie',
		], $player->matchData());
	}

	protected function createLobbyWithGame() : array
	{
		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);
		$lobby->leader->update(['ready' => true]);

		$lobby->refresh();

		$lobby->match->state()->set('turn', $player->id);
		$lobby->match->state()->set('x', $player->id);
		$lobby->match->state()->set('o', $lobby->leader->id);

		return [$lobby, $player];
	}
}
