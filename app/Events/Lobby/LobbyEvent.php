<?php

namespace App\Events\Lobby;

use App\Events\BroadcastEvent;
use App\Models\Lobby;
use Illuminate\Broadcasting\PrivateChannel;

abstract class LobbyEvent extends BroadcastEvent
{
	protected $lobby;

	public function __construct(Lobby $lobby)
	{
		$this->lobby = $lobby;
	}

	public function broadcastOn() : PrivateChannel
	{
		return new PrivateChannel("lobby.{$this->lobby->id}");
	}
}
