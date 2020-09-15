<?php

namespace App\Games\TicTacToe;

use App\Games\Instance as BaseInstance;

class Instance extends BaseInstance
{
	public function init()
	{
		$players = $this->match->lobby->members->shuffle();

		$this->state()->set('x', $players[0]->id);
		$this->state()->set('o', $players[1]->id);

		$this->state()->set('turn', $players[0]->id);

		$this->state()->set('board', [[null, null, null], [null, null, null], [null, null, null]]);
	}
}
