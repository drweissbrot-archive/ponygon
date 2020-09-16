<?php

namespace App\Http\Controllers;

use App\Models\Lobby;

class JoinLobbyController extends Controller
{
	public function __invoke(Lobby $lobby)
	{
		return redirect(config('app.url') . '/play')->withFragment($lobby->id);
	}
}
