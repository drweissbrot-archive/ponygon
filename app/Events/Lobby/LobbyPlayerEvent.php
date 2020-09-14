<?php

namespace App\Events\Lobby;

use App\Models\Lobby;
use App\Models\Player;

abstract class LobbyPlayerEvent extends LobbyEvent
{
	public $lobby;

	protected $player;

	public function __construct(Lobby $lobby, Player $player)
	{
		$this->lobby = $lobby;
		$this->player = $player;
	}

	public function broadcastWith() : array
	{
		return [
			'player' => $this->player->resource()->resolve(),
		];
	}
}
