<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Auth;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
	public function create(Request $request)
	{
		$this->validate($request, [
			'name' => 'required|string|between:3,32',
		]);

		$player = Player::create($request->only('name'));

		Auth::login($player, true);

		return $player->resource();
	}
}
