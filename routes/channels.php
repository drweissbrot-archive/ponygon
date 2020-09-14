<?php

use App\Models\Lobby;
use App\Models\Player;

Broadcast::channel('lobby.{lobby}', function (Player $player, Lobby $lobby) {
	return $player->inLobby($lobby);
});
