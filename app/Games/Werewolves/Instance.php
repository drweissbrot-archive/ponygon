<?php

namespace App\Games\Werewolves;

use App\Games\Instance as BaseInstance;
use App\Models\Player;

class Instance extends BaseInstance
{
	public function init()
	{
		// TODO
	}

	public function dataForPlayer(Player $player) : array
	{
		return [];
	}
}
