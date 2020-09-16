<?php

namespace Tests\Feature\Games\TicTacToe;

use App\Events\Player\MatchData;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RematchTest extends TestCase
{
	use RefreshDatabase;

	public function test_players_can_initiate_a_rematch()
	{
		Event::fake([MatchData::class]);

		[$lobby, $player] = $this->createLobbyWithGame();
		$lobby->match->state()->set('score.x', 1);
		$lobby->match->state()->set('score.o', 3);
		$lobby->match->state()->set('board', [['x', 'o', 'o'], ['o', 'x', 'x'], ['o', 'x', 'o']]);
		$lobby->match->state()->set('winner', 'tie');

		$this->actingAs($player)
			->postJson("/api/match/{$lobby->match->id}/rematch")
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

		$this->assertEquals($player->id, $lobby->match->state('turn'));
		$this->assertEquals(
			[[null, null, null], [null, null, null], [null, null, null]],
			$lobby->match->state('board'),
		);

		$this->assertSimilar([
			'scoreboard' => [
				$player->id => ['role' => 'X', 'score' => 1],
				$lobby->leader->id => ['role' => 'O', 'score' => 3],
			],

			'board' => [[null, null, null], [null, null, null], [null, null, null]],
			'turn' => $player->id,
			'winner' => false,
		], $player->matchData());
	}

	public function test_players_cannot_initiate_a_rematch_for_an_inactive_match()
	{
		Event::fake([MatchData::class]);

		[$lobby, $player] = $this->createLobbyWithGame();
		$lobby->match->state()->set('board', [[null, 'x', null], [null, 'o', null], [null, null, null]]);
		$lobby->match->state()->set('winner', 'tie');

		$lobby->update(['match_id' => null]);

		$this->actingAs($player)
			->postJson("/api/match/{$lobby->match->id}/rematch")
			->assertNotFound();

		Event::assertDispatched(MatchData::class, 0);
	}

	public function test_players_cannot_initiate_a_rematch_for_a_match_that_has_not_ended()
	{
		Event::fake([MatchData::class]);

		[$lobby, $player] = $this->createLobbyWithGame();
		$lobby->match->state()->set('board', [[null, 'x', null], [null, 'o', null], [null, null, null]]);

		$this->actingAs($player)
			->postJson("/api/match/{$lobby->match->id}/rematch")
			->assertForbidden();

		Event::assertDispatched(MatchData::class, 0);

		$lobby->match->refresh();

		$this->assertEquals($player->id, $lobby->match->state('turn'));
		$this->assertEquals(
			[[null, 'x', null], [null, 'o', null], [null, null, null]],
			$lobby->match->state('board'),
		);

		$this->assertSimilar([
			'scoreboard' => [
				$player->id => ['role' => 'X', 'score' => 0],
				$lobby->leader->id => ['role' => 'O', 'score' => 0],
			],

			'board' => [[null, 'x', null], [null, 'o', null], [null, null, null]],
			'turn' => $player->id,
			'winner' => false,
		], $player->matchData());
	}

	public function test_out_of_lobby_and_unauthenticated()
	{
		Event::fake([MatchData::class]);

		[$lobby, $player] = $this->createLobbyWithGame();

		$this->postJson("/api/match/{$lobby->match->id}/rematch")
			->assertUnauthorized();

		$this->actingAs(Player::factory()->create())
			->postJson("/api/match/{$lobby->match->id}/rematch")
			->assertForbidden();

		Event::assertDispatched(MatchData::class, 0);

		$lobby->match->refresh();

		$this->assertEquals($player->id, $lobby->match->state('turn'));
		$this->assertEquals(
			[[null, null, null], [null, null, null], [null, null, null]],
			$lobby->match->state('board'),
		);

		$this->assertSimilar([
			'scoreboard' => [
				$player->id => ['role' => 'X', 'score' => 0],
				$lobby->leader->id => ['role' => 'O', 'score' => 0],
			],

			'board' => [[null, null, null], [null, null, null], [null, null, null]],
			'turn' => $player->id,
			'winner' => false,
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
