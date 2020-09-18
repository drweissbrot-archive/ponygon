<?php

namespace App\Http\Controllers\Api;

use App\Events\Lobby\PlayerKicked;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Lobby\GameConfigRequest;
use App\Http\Requests\Api\Lobby\KickRequest;
use App\Http\Requests\Api\Lobby\PromoteToLeaderRequest;
use App\Models\Lobby;
use Arr;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Response;

class LobbyController extends Controller
{
	public function read(Request $request, Lobby $lobby)
	{
		abort_unless($request->user()->lobby_id === $lobby->id, 403);

		return $lobby->load('members', 'match')->resource();
	}

	public function create(Request $request)
	{
		abort_if($request->user()->lobby, 403);

		$lobby = Lobby::create(['leader_id' => $request->user()->id]);

		return $lobby->resource();
	}

	public function join(Request $request, Lobby $lobby)
	{
		abort_if($request->user()->lobby, 403);

		$lobby->addMember($request->user());

		return ($request->user()->wasChanged('name'))
			? $lobby->resource()->additional(['replaced_name' => $request->user()->name])
			: $lobby->resource();
	}

	public function gameConfig(GameConfigRequest $request, Lobby $lobby)
	{
		$validated = $request->validated();

		throw_if(empty($validated), ValidationException::withMessages([
			'game_options' => ['You must provide a game option to change.'],
		]));

		$key = array_keys($validated)[0];
		$value = $validated[$key];

		$config = $lobby->game_config;

		throw_unless(Arr::has($config, $key), ValidationException::withMessages([
			$key => ['Invalid game config option.'],
		]));

		Arr::set($config, $key, $value);

		$lobby->update(['game_config' => $config]);

		return Response::noContent();
	}

	public function kick(KickRequest $request, Lobby $lobby)
	{
		$player = $lobby->members()->find($request->player);
		$player->delete();

		PlayerKicked::dispatch($lobby, $player);

		return Response::noContent();
	}

	public function promoteToLeader(PromoteToLeaderRequest $request, Lobby $lobby)
	{
		$player = $lobby->members()->find($request->player);
		$lobby->update(['leader_id' => $player->id]);

		return Response::noContent();
	}
}
