<?php

namespace Tests\Feature\Games\TicTacToe;

use App\Events\Player\MatchData;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoveTest extends TestCase
{
	use RefreshDatabase;

	public function test_player_can_make_a_move()
	{
		Event::fake([MatchData::class]);

		[$lobby, $player] = $this->createLobbyWithGame();

		$this->actingAs($player)
			->postJson("/api/match/{$lobby->match->id}/move", ['x' => 0, 'y' => 0])
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
			[['x', null, null], [null, null, null], [null, null, null]],
			$lobby->match->state('board'),
		);

		$this->assertSimilar([
			'scoreboard' => [
				$player->id => [
					'role' => 'X',
					'score' => 0,
				],
				$lobby->leader->id => [
					'role' => 'O',
					'score' => 0,
				],
			],

			'board' => [['x', null, null], [null, null, null], [null, null, null]],
			'turn' => $lobby->leader->id,
			'winner' => false,
		], $player->matchData());
	}

	public function test_players_cannot_claim_a_node_that_is_already_claimed()
	{
		Event::fake([MatchData::class]);

		[$lobby, $player] = $this->createLobbyWithGame();

		$lobby->match->state()->set('board.1.2', 'o');

		$this->actingAs($player)
			->postJson("/api/match/{$lobby->match->id}/move", ['x' => 1, 'y' => 2])
			->assertStatus(422)
			->assertJsonValidationErrors([
				'x' => 'The provided node is already claimed.',
			]);

		Event::assertDispatched(MatchData::class, 0);

		$lobby->match->refresh();

		$this->assertEquals($player->id, $lobby->match->state('turn'));
		$this->assertEquals(
			[[null, null, null], [null, null, 'o'], [null, null, null]],
			$lobby->match->state('board'),
		);

		$this->assertSimilar([
			'scoreboard' => [
				$player->id => ['role' => 'X', 'score' => 0],
				$lobby->leader->id => ['role' => 'O', 'score' => 0],
			],

			'board' => [[null, null, null], [null, null, 'o'], [null, null, null]],
			'turn' => $player->id,
			'winner' => false,
		], $player->matchData());
	}

	public function test_players_cannot_make_a_move_when_its_not_their_turn()
	{
		Event::fake([MatchData::class]);

		[$lobby, $player] = $this->createLobbyWithGame();

		$this->actingAs($lobby->leader)
			->postJson("/api/match/{$lobby->match->id}/move", ['x' => 0, 'y' => 0])
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

	public function test_out_of_lobby_and_unauthenticated()
	{
		Event::fake([MatchData::class]);

		[$lobby, $player] = $this->createLobbyWithGame();

		$this->postJson("/api/match/{$lobby->match->id}/move", ['x' => 0, 'y' => 0])
			->assertUnauthorized();

		$this->actingAs(Player::factory()->create())
			->postJson("/api/match/{$lobby->match->id}/move", ['x' => 0, 'y' => 0])
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
