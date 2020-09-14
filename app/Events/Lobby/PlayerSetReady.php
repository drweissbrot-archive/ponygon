<?php

namespace App\Events\Lobby;

class PlayerSetReady extends LobbyPlayerEvent
{
	public function broadcastWith() : array
	{
		return [
			'player' => $this->player->id,
			'ready' => $this->player->ready,
		];
	}
}
