<?php

namespace App\Jobs\Lobby;

use App\Events\Lobby\GameStarting;
use App\Games\Game;
use App\Jobs\QueuedJob;
use App\Models\Game as GameModel;
use App\Models\Lobby;

class StartGame extends QueuedJob
{
	public $game;

	public $lobby;

	public function __construct(Lobby $lobby, GameModel $game)
	{
		$this->lobby = $lobby;
		$this->game = $game;
	}

	public function handle()
	{
		$this->lobby->refresh();

		if ($this->game->id !== $this->lobby->game_id) {
			return; // the lobby's current game is not the game this job was queued for, so don't start the game
		}

		if ($this->lobby->gameCanBeStarted()) {
			$this->game->instance()->init();

			GameStarting::dispatch($this->lobby, $this->game);
		} else {
			$this->lobby->cancelGame();
		}
	}
}
