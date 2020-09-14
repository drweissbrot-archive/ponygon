<?php

namespace App\Observers;

use App\Events\Lobby\GameCancelled;
use App\Events\Lobby\GameConfigChanged;
use App\Events\Lobby\GameWillStart;
use App\Events\Lobby\PlayerPromotedToLeader;
use App\Jobs\Lobby\StartGame;
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
		$this->autostartGame($lobby);
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

	protected function autostartGame(Lobby $lobby) : void
	{
		if (! $lobby->isDirty('game_id')) {
			return;
		}

		if ($lobby->game_id) {
			if ($lobby->game_id !== optional($lobby->game)->id) {
				$lobby->load('game');
			}

			GameWillStart::dispatch($lobby);
			StartGame::dispatch($lobby, $lobby->game)->delay(5);
		} else {
			GameCancelled::dispatch($lobby);
		}
	}
}
