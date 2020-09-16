<?php

namespace App\Events\Player;

use App\Models\Match;
use App\Models\Player;

class MatchStarting extends PlayerEvent
{
	protected $match;

	public function __construct(Player $player, Match $match)
	{
		parent::__construct($player);

		$this->match = $match;
	}

	public function broadcastWith() : array
	{
		return [
			'match' => $this->match->resource()->resolve(),
			'data' => $this->player->matchData(),
		];
	}
}
