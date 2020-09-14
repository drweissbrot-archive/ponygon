<?php

namespace App\Http\Controllers;

use App\Models\Lobby;

class JoinLobbyController extends Controller
{
	public function __invoke(Lobby $lobby)
	{
		return redirect('/play')->withFragment($lobby->id);
	}
}
