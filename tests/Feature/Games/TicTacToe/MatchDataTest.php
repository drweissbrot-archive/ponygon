<?php

namespace Tests\Feature\Games\TicTacToe;

use App\Models\Lobby;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchData extends TestCase
{
	use RefreshDatabase;

	public function test_it_returns_match_data()
	{
		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		$this->assertNull($player->matchData());

		$lobby->leader->update(['ready' => true]);

		$lobby->refresh();

		$this->assertEquals([
			'scoreboard' => [
				$lobby->leader->id => [
					'score' => 0,
					'role' => $lobby->match->state('x') === $lobby->leader->id ? 'X' : 'O',
				],
				$player->id => [
					'score' => 0,
					'role' => $lobby->match->state('x') === $player->id ? 'X' : 'O',
				],
			],
			'turn' => $lobby->match->state('x'),
		], $player->matchData());

		$this->assertEquals($player->matchData(), $lobby->match->instance()->dataForPlayer($player));
	}
}
