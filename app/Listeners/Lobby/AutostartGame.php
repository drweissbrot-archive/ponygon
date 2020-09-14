<?php

namespace App\Listeners\Lobby;

use App\Listeners\Listener;

class AutostartGame extends Listener
{
	public function handle($event)
	{
		$lobby = $event->lobby->refresh();

		if (! $lobby->gameCanBeStarted()) {
			if ($lobby->game) {
				$lobby->cancelGame();
			}

			return;
		}

		if (! $lobby->game) {
			$lobby->createGame();
		}
	}
}
