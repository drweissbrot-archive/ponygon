<?php

namespace App\Observers;

use App\Events\Lobby\PlayerJoined;
use App\Events\Lobby\PlayerLeft;
use App\Events\Lobby\PlayerSetReady;
use App\Models\Player;
use Str;

class PlayerObserver
{
	public function creating(Player $player)
	{
		if ($player->token === null) {
			$player->token = '';
		}

		$this->preventDuplicateNicknames($player, true);
	}

	public function updating(Player $player)
	{
		$this->preventDuplicateNicknames($player);
	}

	public function saved(Player $player)
	{
		$this->broadcastJoiningAndLeavingLobbies($player);
		$this->broadcastReady($player);
	}

	public function deleted(Player $player)
	{
		$this->broadcastLeavingLobby($player);
		$this->passLobbyLeaderOrCloseLobby($player);
	}

	protected function preventDuplicateNicknames(Player $player, bool $bypassLobbyDirtyCheck = false) : void
	{
		if ($player->lobby_id === null || (! $player->isDirty('lobby_id') && ! $bypassLobbyDirtyCheck)) {
			return;
		}

		if ($player->lobby_id !== optional($player->lobby)->id) {
			$player->load('lobby.members');
		}

		$allNames = $player->lobby->members->pluck('name');
		$occurences = 1;

		foreach ($allNames as $name) {
			if ($name === $player->name) {
				$occurences++;

				if ($occurences > 1) {
					break;
				}
			}
		}

		if ($occurences < 2) {
			return;
		}

		$highestOccurence = $allNames->filter(function ($name) use ($player) {
			return Str::startsWith($name, $player->name);
		})->sort()->last();

		if ($highestOccurence === $player->name) {
			$player->name .= '_1';
		} else {
			[$_, $number] = preg_split('/_*\K_/u', $highestOccurence);

			$player->name .= '_' . ($number + 1);
		}
	}

	protected function broadcastReady(Player $player) : void
	{
		if ($player->isDirty('ready') && $player->inLobby()) {
			PlayerSetReady::dispatch($player->lobby, $player);
		}
	}

	protected function broadcastJoiningAndLeavingLobbies(Player $player) : void
	{
		if (! $player->isDirty('lobby_id')) {
			return;
		}

		$previous = $player->getOriginal('lobby_id');

		if ($previous) {
			$lobby = ($player->lobby->id === $previous)
				? $player->lobby
				: Lobby::find($previous);

			PlayerLeft::dispatch($lobby, $player);
		}

		if ($player->lobby_id) {
			if ($player->lobby_id !== optional($player->lobby)->id) {
				$player->load('lobby');
			}

			PlayerJoined::dispatch($player->lobby, $player);
		}
	}

	protected function broadcastLeavingLobby(Player $player) : void
	{
		if ($player->lobby_id) {
			PlayerLeft::dispatch($player->lobby, $player);
		}
	}

	protected function passLobbyLeaderOrCloseLobby(Player $player) : void
	{
		if (! $player->lobby_id || $player->lobby->leader_id !== $player->id) {
			return;
		}

		$lobby = $player->load('lobby.members')->lobby;

		($lobby->members->count() === 0)
			? $lobby->delete()
			: $lobby->update(['leader_id' => $lobby->members->random()->id]);
	}
}
