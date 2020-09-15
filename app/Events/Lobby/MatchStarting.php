<?php

namespace App\Events\Lobby;

class MatchStarting extends LobbyEvent
{
	public function broadcastWith() : array
	{
		return $this->lobby->match->resource()->resolve();
	}
}
