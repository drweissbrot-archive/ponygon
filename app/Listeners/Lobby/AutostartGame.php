<?php

namespace App\Listeners\Lobby;

use App\Listeners\Listener;
use App\Models\Lobby;

class AutostartGame extends Listener
{
	public function handle($event)
	{
		$lobby = $event->lobby->refresh();

		if (! $lobby->game_config['selected_game'] || $this->invalidPlayerCount($lobby)) {
			if ($lobby->game) {
				$lobby->cancelGame();
			}

			return;
		}

		$hasReady = false;
		$hasUnready = false;

		foreach ($lobby->members as $player) {
			if ($player->ready) {
				$hasReady = true;
			} else {
				$hasUnready = true;
			}

			if (($hasReady && $hasUnready) || ($hasUnready && $lobby->game)) {
				break;
			}
		}

		if ($hasUnready) {
			if ($lobby->game) {
				$lobby->cancelGame();
			}
		} elseif ($hasReady && ! $lobby->game) {
			$lobby->createGame();
		}
	}

	protected function invalidPlayerCount(Lobby $lobby) : bool
	{
		$playerCount = $lobby->members->count();
		$limits = $lobby->game_config[$lobby->game_config['selected_game']]['playerCount'];

		$min = $limits['min'] ?? false;
		$max = $limits['max'] ?? false;

		return ($min !== false && $playerCount < $min) || ($max !== false && $playerCount > $max);
	}
}
