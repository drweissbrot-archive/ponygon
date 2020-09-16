<?php

namespace App\Events\Player;

use App\Events\BroadcastEvent;
use App\Models\Player;
use Illuminate\Broadcasting\PrivateChannel;

abstract class PlayerEvent extends BroadcastEvent
{
	protected $player;

	public function __construct(Player $player)
	{
		$this->player = $player;
	}

	public function broadcastOn() : PrivateChannel
	{
		return new PrivateChannel("player.{$this->player->id}");
	}
}
