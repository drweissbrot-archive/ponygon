<?php

namespace App\Observers;

use App\Events\Lobby\GameConfigChanged;
use App\Events\Lobby\MatchCancelled;
use App\Events\Lobby\MatchWillStart;
use App\Events\Lobby\PlayerPromotedToLeader;
use App\Jobs\Lobby\StartMatch;
use App\Models\Lobby;

class LobbyObserver
{
	public function saved(Lobby $lobby) : void
	{
		$this->addLeaderAsMember($lobby);
	}

	public function updated(Lobby $lobby) : void
	{
		$this->broadcastGameConfigChanges($lobby);
		$this->broadcastLeaderChanges($lobby);
		$this->autostartMatch($lobby);
	}

	protected function addLeaderAsMember(Lobby $lobby) : void
	{
		if (! $lobby->leader) {
			$lobby->load('leader');
		}

		if ($lobby->leader->lobby_id !== $lobby->id) {
			$lobby->members()->save($lobby->leader);
		}
	}

	protected function broadcastGameConfigChanges(Lobby $lobby) : void
	{
		if ($lobby->isDirty('game_config')) {
			GameConfigChanged::dispatch($lobby);
		}
	}

	protected function broadcastLeaderChanges(Lobby $lobby) : void
	{
		if ($lobby->isDirty('leader_id')) {
			if ($lobby->leader->id !== $lobby->leader_id) {
				$lobby->load('leader');
			}

			PlayerPromotedToLeader::dispatch($lobby, $lobby->leader);
		}
	}

	protected function autostartMatch(Lobby $lobby) : void
	{
		if (! $lobby->isDirty('match_id')) {
			return;
		}

		if ($lobby->match_id) {
			if ($lobby->match_id !== optional($lobby->match)->id) {
				$lobby->load('match');
			}

			MatchWillStart::dispatch($lobby);
			StartMatch::dispatch($lobby, $lobby->match)->delay(5);
		} else {
			MatchCancelled::dispatch($lobby);
		}
	}
}
