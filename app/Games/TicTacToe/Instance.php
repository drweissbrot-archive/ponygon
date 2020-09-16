<?php

namespace App\Games\TicTacToe;

use App\Games\Instance as BaseInstance;
use App\Models\Player;

class Instance extends BaseInstance
{
	public function init()
	{
		$players = $this->match->lobby->members->shuffle();

		$this->state()->initialize([
			'x' => $players[0]->id,
			'o' => $players[1]->id,

			'turn' => $players[0]->id,
			'board' => [[null, null, null], [null, null, null], [null, null, null]],
			'score' => ['x' => 0, 'o' => 0],
		]);
	}

	public function dataForPlayer(Player $player) : array
	{
		return [
			'scoreboard' => [
				$this->state('x') => [
					'role' => 'X', 'score' => $this->state('score.x'),
				],
				$this->state('o') => [
					'role' => 'O', 'score' => $this->state('score.o'),
				],
			],

			'turn' => $this->state('turn'),
			'board' => $this->state('board'),
		];
	}
}
