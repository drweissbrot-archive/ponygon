<?php

namespace App\Events\Lobby;

class GameConfigChanged extends LobbyEvent
{
	public $lobby;

	public function broadcastWith() : array
	{
		$game = $this->lobby->game_config['selected_game'];

		return [
			'selected_game' => $game,
			$game => $this->lobby->game_config[$game],
		];
	}
}
