<?php

namespace App\Jobs\Lobby;

use App\Events\Lobby\MatchStarting;
use App\Jobs\QueuedJob;
use App\Models\Lobby;
use App\Models\Match;

class StartMatch extends QueuedJob
{
	public $lobby;

	public $match;

	public function __construct(Lobby $lobby, Match $match)
	{
		$this->lobby = $lobby;
		$this->match = $match;
	}

	public function handle()
	{
		$this->lobby->refresh();

		if ($this->match->id !== $this->lobby->match_id) {
			return; // the lobby's current match is not the match this job was queued for, so don't start the match
		}

		if ($this->lobby->matchCanBeStarted()) {
			$this->match->instance()->init();

			MatchStarting::dispatch($this->lobby, $this->match);
		} else {
			$this->lobby->cancelMatch();
		}
	}
}
