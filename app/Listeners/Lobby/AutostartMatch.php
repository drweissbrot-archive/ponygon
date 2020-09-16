<?php

namespace App\Listeners\Lobby;

use App\Listeners\Listener;

class AutostartMatch extends Listener
{
	public function handle($event)
	{
		$lobby = $event->lobby->refresh();

		if (! $lobby->matchCanBeStarted()) {
			if ($lobby->match) {
				$lobby->cancelMatch();
			}

			return;
		}

		if (! $lobby->match) {
			$lobby->createMatch();
		}
	}
}
