<?php

namespace App\Events\Player;

use App\Models\Player;

class MatchData extends PlayerEvent
{
	protected $matchData;

	public function __construct(Player $player, array $matchData = null)
	{
		parent::__construct($player);

		$this->matchData = $matchData;
	}

	public function broadcastWith() : array
	{
		return $this->matchData ?: $this->player->matchData();
	}
}
