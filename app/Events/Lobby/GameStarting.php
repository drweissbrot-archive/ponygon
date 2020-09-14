<?php

namespace App\Events\Lobby;

class GameStarting extends LobbyEvent
{
	public function broadcastWith() : array
	{
		return $this->lobby->game->resource()->resolve();
	}
}
